<?php
// src/Command/WsServerCommand.php
namespace App\Command;

use App\Base\Command\AbstractCommand;
use App\Service\WsServerAppService;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WsServerCommand extends AbstractCommand
{
    protected static $defaultName = 'ws:start';

    protected function configure()
    {
        $this->setDescription('Start a web socket server');
        $this->setHelp('This command allows you start a ws server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = IoServer::factory(new HttpServer(
            new WsServer(
                $this->container->get(WsServerAppService::class)
            )
        ), 8080);

        $server->run();
    }
}
