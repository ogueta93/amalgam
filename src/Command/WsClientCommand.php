<?php
// src/Command/WsClientCommand.php
namespace App\Command;

use App\Base\Command\AbstractCommand;
use App\Base\Constant\CronEventConstant;
use App\Command\Traits\WsClientCommandTrait;
use App\Entity\CronEvent;
use App\Entity\CronEventType;
use App\Service\JWToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Ratchet\Client\Connector as RachetConnector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory;
use React\Socket\Connector;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Security;

class WsClientCommand extends AbstractCommand
{
    use WsClientCommandTrait;

    const CREDENTIALS_FIELD = 'loggedEmail';
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected static $defaultName = 'ws:client';

    protected $jwToken = null;
    protected $process = null;
    protected $queue = [];
    protected $recivedMsgCount = 0;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->jwToken = $container->get(JWToken::class);

        parent::__construct($container, $em, $security);
    }

    protected function configure()
    {
        $this->setDescription('Ws client created for multiple objectives');
        $this->setHelp('This command allows you to communicate with the wsServer');

        $this->addArgument('process', InputArgument::REQUIRED, 'Process to execute');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->process = $input->getArgument('process');

        $loop = Factory::create();
        $reactConnector = new Connector($loop, [
            'dns' => '8.8.8.8',
            'timeout' => 1
        ]);
        $connector = new RachetConnector($loop, $reactConnector);

        $connector('ws://127.0.0.1:8080')
            ->then(function (WebSocket $conn) {
                $conn->on('message', function (MessageInterface $msg) use ($conn) {
                    echo "Received: {$msg}\n";

                    $this->processResponseMsg($msg);

                    if (\count($this->queue) === 0 || \count($this->queue) === $this->recivedMsgCount) {
                        echo "The queue is empty, the connection is close\n";
                        $conn->close();
                    }
                });

                $conn->on('close', function ($code = null, $reason = null) {
                    echo "Connection closed ({$code} - {$reason})\n";
                });

                $data = $this->getProcessesData();
                if ($data) {
                    foreach ($data as $key => $dataToSent) {
                        $conn->send($this->getEncodedMessage($dataToSent));
                    }
                } else {
                    echo "There are not any data\n";
                    $conn->close();
                }
            }, function (\Exception $e) use ($loop) {
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });

        $loop->run();
    }

    /**
     * Gets the response msg and processes it
     *
     * @param string $msg
     * @return void
     */
    protected function processResponseMsg(string $msg)
    {
        $decodedMsg = $this->getDecodedMsg($msg);
        $keyId = $decodedMsg['cre'] ?? null;

        $cronEventTypeEnt = $this->em->getRepository(CronEvent::class)->findOneBy(['keyId' => $keyId]);
        if ($cronEventTypeEnt) {
            $this->em->remove($cronEventTypeEnt);
            $this->em->flush();

            if (($key = array_search($keyId, $this->queue)) !== false) {
                unset($this->queue[$key]);
            }
        }

        $this->recivedMsgCount++;
    }

    /**
     * Gets the preocesses data
     *
     * @return array|null
     */
    protected function getProcessesData(): ?array
    {
        $processesEnt = $this->getProcesses();
        if (!$processesEnt) {
            return null;
        }

        $data = [];
        foreach ($processesEnt as $key => $cronEvent) {
            $this->queue[] = $cronEvent->getKeyId();
            $data[] = \array_merge($this->getEventBody($cronEvent), $this->getEventExtra($cronEvent));
        }

        return $data;
    }

    /**
     * Get the processes by a custom query
     *
     * @return array|null
     */
    protected function getProcesses(): ?array
    {
        $cronEventTypeEnt = $this->em->getRepository(CronEventType::class)->findOneBy(['name' => $this->process]);
        $now = new \DateTime();

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('ce')
            ->from(CronEvent::class, 'ce')
            ->join('ce.cronEventType', 'cet', 'WITH', 'cet.id = :cronEventType')
            ->where('ce.expiredDateTime <= :now')
            ->setParameters([
                'cronEventType' => $cronEventTypeEnt->getId(),
                'now' => $now->format(self::DATE_TIME_FORMAT)
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets the body data from the event
     *
     * @param CronEvent $email
     * @return array
     */
    protected function getEventBody(CronEvent $cronEvent): array
    {
        $cronEventData = \json_decode($cronEvent->getData(), true);

        switch ($cronEvent->getCronEventType()->getId()) {
            case CronEventConstant::BATTLE_REWARD_EVENT:
                return $this->getBattleRewardEventBody($cronEventData);
                break;
            default:
                break;
        }
    }

    /**
     * Gets the extra data from the event
     *
     * @param CronEvent $email
     * @return array
     */
    protected function getEventExtra(CronEvent $cronEvent): array
    {
        $cronEventData = \json_decode($cronEvent->getData(), true);

        return [
            'ev' => [
                'l' => 'en-US'
            ],
            't' => $this->jwToken->create($cronEventData[self::CREDENTIALS_FIELD]),
            'cre' => $cronEvent->getKeyId()
        ];
    }

    /**
     * Gets the encoded message before to sends it
     *
     * @param array $data
     * @return string
     */
    protected function getEncodedMessage(array $data): string
    {
        return \base64_encode(\json_encode($data));
    }

    /**
     * Gets the decoded msg
     *
     * @param string $msg
     * @return array
     */
    protected function getDecodedMsg(string $msg): array
    {
        return \json_decode(base64_decode($msg), true);
    }
}
