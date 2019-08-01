<?php
// src/Business/Battle/Builder/BattleBuilder.php
namespace App\Business\Battle\Builder;

use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Business\Battle\Constant\BattleUserStatusConstant;
use App\Entity\BattleStatus;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class BattleBuilder
{
    use WsUtilsTrait;

    /** Constants */
    const FORMAT_DATE = 'Y-m-d H:i:s';
    const SPECIAL_MOVE_SAME = 'same';
    const SPECIAL_MOVE_SUM = 'sum';
    const MAX_BATTLE_BOARD_FIELDS = 9;

    /** Node Constants */
    const NODE_LAST_CHANGE = 'lastChange';
    const NODE_STATUS = 'status';
    const NODE_USER = 'user';
    const NODE_USERS = 'users';
    const NODE_USER_ID = 'userId';
    const NODE_CARD = 'card';
    const NODE_CARDS = 'cards';
    const NODE_CHECKED = 'checked';
    const NODE_ROW = 'row';
    const NODE_FIELD = 'field';
    const NODE_CARDS_COUNT = 'cardsCount';

    const NODE_PROGRESS = 'progress';
    const NODE_PROGRESS_MAIN = 'main';
    const NODE_PROGRESS_TURNS = 'turns';
    const NODE_MAIN_CARDS_SELECTION = 'cardsSelection';
    const NODE_MAIN_PHASE = 'phase';
    const NODE_MAIN_COIN_THROW = 'cointThrow';
    const NODE_MAIN_BATTLE_FIELD = 'battleField';
    const NODE_MAIN_BATTLE_RESULT = 'battleResult';
    
    const NODE_BATTLE_RESULT_WINNER = 'winner';
    const NODE_BATTLE_RESULT_LOSER = 'loser';
    const NODE_BATTLE_RESULT_DRAW = 'draw';

    const NODE_CARD_USER_CARD_ID = 'userCardId';
    const NODE_CARD_CAPTURED = 'captured';
    const NODE_CARD_PLACED = 'placed';

    const NODE_BATTLE_BOARD = 'board';
    const NODE_TURN_MOVEMENT = 'movement';
    const NODE_TURN_COMPLETED = 'completed';

    const NODE_MOVEMENT_CARDS_CAPTURED = 'cardsCaptured';
    const NODE_MOVEMENT_COMBO = 'combo';
    const NODE_MOVEMENT_COORDINATES = 'coordinates';

    const NODE_RELATIVE_POSITION_TOP = 'top';
    const NODE_RELATIVE_POSITION_LEFT = 'left';
    const NODE_RELATIVE_POSITION_BOTTOM = 'bottom';
    const NODE_RELATIVE_POSITION_RIGHT = 'right';

    const NODE_CAPTURED_NORMAL = 'normal';
    const NODE_CAPTURED_SPECIAL = 'special';

    /** Properties */
    protected $battleData;
    protected $user;
    protected $specialMove;

    /**
     * Default Constructor
     * 
     * @param array $battleData
     */
    public function __construct(array $battleData = []) 
    {
        $this->battleData = $battleData;
        $this->user = $this->getLoggedUser();
    }

    /**
     * Creates a battle base array structure
     *
     * @param array $dataHelper
     * @return array
     */
    public function createBattleBase(array $dataHelper): array
    {
        $this->battleData = [
            'id' => $dataHelper['battle']['id'],
            'type' => $dataHelper['battle']['type'],
            self::NODE_STATUS => $dataHelper['battle']['status'],
            'createdBy' => $dataHelper['User']->toArray(),
            self::NODE_USERS => $this->getUsersNode($dataHelper),
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];

        return $this->battleData;
    }

    /**
     * Adds the essential information for accepted current battle
     *
     * @param BattleStatus $battleStatus
     * @return array
     */
    public function acceptNewBattle(BattleStatus $battleStatus): array
    {
        $this->battleData[self::NODE_STATUS] = $battleStatus->toArray();
        $this->battleData[self::NODE_LAST_CHANGE] = $this->setLastChange();

        foreach ($this->battleData['users'] as $key => &$user) {
            if ($user['statusId'] == BattleUserStatusConstant::PENDING) {
                $user['statusId'] = BattleUserStatusConstant::ACCEPTED;
                $user['lastChange'] = $this->setLastChange();
            }
        }

        $this->battleData[self::NODE_PROGRESS] = [
            self::NODE_PROGRESS_MAIN => [self::NODE_MAIN_PHASE => BattleMainProgressPhaseConstant::CARD_SELECTION_PHASE]
        ];

        return $this->battleData;
    }

    /**
     * Add the user's cards selection to the battleData
     *
     * @param array $cardsSelected
     * @return array
     */
    public function addUserCardsSelection(array $cardsSelected): array
    {
        $selection = \array_map(function ($card) {
            return \array_merge(
                ['userCardId' => $card->getId()],
                $card->getIdCard()->toArray(),
                [self::NODE_CARD_PLACED => false],
                [self::NODE_CARD_CAPTURED => false]
            );
        }, $cardsSelected);

        $nodeCardsSelection = $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] ?? [];
        $nodeCardsSelection[] = [
            self::NODE_USER_ID => $this->getLoggedUser()->getId(),
            self::NODE_CARDS => $selection,
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];

        $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] = $nodeCardsSelection;

        if (\count($nodeCardsSelection) === 2) {
            $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_PHASE] = BattleMainProgressPhaseConstant::COIN_THROW_PHASE;

            $usersIds = \array_map(function ($element) {
                return $element['userId'];
            }, $nodeCardsSelection);

            $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_COIN_THROW] = $this->getCoinThrowNode($usersIds);
        }

        $this->battleData[self::NODE_LAST_CHANGE] = $this->setLastChange();

        return $this->battleData;
    }

    /**
     * Add the user's cards selection to the battleData
     *
     * @return array
     */
    public function setUserShowCoinThrow(): array
    {
        $today = new \DateTime();
        $completeShow = true;

        foreach ($this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_COIN_THROW] as $key => &$userCoinThrow) {
            if ($userCoinThrow[self::NODE_USER_ID] === $this->getLoggedUser()->getId()) {
                $userCoinThrow[self::NODE_CHECKED] = true;
                $userCoinThrow[self::NODE_LAST_CHANGE] = $this->setLastChange();
            }

            if ($userCoinThrow[self::NODE_CHECKED] === false) {
                $completeShow = false;
            }
        }

        if ($completeShow) {
            $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_PHASE] = BattleMainProgressPhaseConstant::BATTLE_PHASE;
            $this->setBattleFieldBase();
        }

        $this->battleData[self::NODE_LAST_CHANGE] = $this->setLastChange();

        return $this->battleData;
    }

    /**
     * Completes the current turn, adds a card in the board and introduces the next turn
     *
     * @param array $data
     *
     * @return array
     */
    public function makeCardMovement(array $data): array
    {
        $userCardId = $data['userCardId'];
        $coordinates = $data['coordinates'];

        /** Setting card in play */
        $cardInPlay = $this->getCardSelectionByUserCardId($userCardId);
        $this->setCardPlacedStatus($cardInPlay);
        $this->setCardInBoard($cardInPlay, $coordinates);

        /** Processing card Movement */
        $this->processCardInplay($cardInPlay, $coordinates);

        /** Process Battle State */ 
        $this->processBattleState();

        $this->battleData[self::NODE_LAST_CHANGE] = $this->setLastChange();

        return $this->battleData;
    }

    /**
     * Process the car that It is playing now
     * 
     * @param array $cardInPlay
     * @param array $coordinates
     */
    protected function processCardInplay(array $cardInPlay, array $coordinates) 
    {
        $capturedCards = $this->getCardsCaptured($cardInPlay, $coordinates);

        $specialCapture = $capturedCards[self::NODE_CAPTURED_SPECIAL] ?? [];
        $normalCapture = $capturedCards[self::NODE_CAPTURED_NORMAL] ?? [];

        $cardsToCapture = \array_merge($specialCapture, $normalCapture);
        if($cardsToCapture) {
            $this->setCapturedCards($cardsToCapture);
        }

        /** Combo logic */
        $combo = $specialCapture ? $this->processCombo($specialCapture) : [];
        
        /** Sets turn movement */
        $this->setTurnMovement($cardInPlay, $cardsToCapture, $coordinates, $combo);
    }

    /**
     * Processes the battle state and the new turns
     * 
     * @return void
     */
    protected function processBattleState()
    {
        if ($this->checkIfBattleBoardIsComplete()) {
            $this->finishBattle();
        } else {
            /** New turn */
            $rival = ($this->getRivalsFromBattleData())[0];
            $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_TURNS][] = $this->addNewTurn($rival['id']);
        }
    }

    /**
     * Finishes battle
     * 
     * @return void
     */
    protected function finishBattle() 
    {
        $rival = ($this->getRivalsFromBattleData())[0];

        $cardsCapturedByUser = $this->getCardsCapturedByUserId($this->user->getId());
        $cardsCapturedByRival = $this->getCardsCapturedByUserId($rival['id']);

        $winner = \count($cardsCapturedByUser) > \count($cardsCapturedByRival) ? $this->user->toArray() : $rival;
        $loser = \count($cardsCapturedByUser) < \count($cardsCapturedByRival) ? $this->user->toArray() : $rival;
        $draw = $winner['id'] === $loser['id'] ? true : false;

        $battleResult = [];
        if ($draw) {
            $battleResult[self::NODE_BATTLE_RESULT_DRAW] = true;
        } else {
            $battleResult[self::NODE_BATTLE_RESULT_WINNER] = [
                self::NODE_USER => $winner,
                self::NODE_CARDS_COUNT => \count($this->getCardsCapturedByUserId($winner['id']))
            ];
            $battleResult[self::NODE_BATTLE_RESULT_LOSER] = [
                self::NODE_USER => $loser,
                self::NODE_CARDS_COUNT => \count($this->getCardsCapturedByUserId($loser['id']))
            ];
        }

        $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_BATTLE_RESULT] = $battleResult;
        $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_PHASE] = BattleMainProgressPhaseConstant::FINISH_PHASE;
    }

    /**
     * Gets and processes combo
     * 
     * @param
     * @return array
     */
    protected function processCombo(array $specialCapture): array
    {
        $combo = [];

        if ($specialCapture) {
            foreach ($specialCapture as $key => $card) {
                $cardCoordinates = $this->getCardCoordinatesInBoard($card);
                $comboCapturedCards = $this->getCardsCaptured($card, $cardCoordinates, false);

                $capturedInCombo = $comboCapturedCards[self::NODE_CAPTURED_NORMAL] ?? null;
                if ($capturedInCombo) {
                    $this->setCapturedCards($capturedInCombo);

                    $combo[] = [
                        self::NODE_CARD => $card[self::NODE_CARD],
                        self::NODE_MOVEMENT_CARDS_CAPTURED => \array_map(function($element) {
                            return $element['card'];
                        }, $capturedInCombo)
                    ];
                }
            }
        }

        return $combo;
    }

    /**
     * Sets Captured Cards
     * 
     * @param array $cardsToCapture
     */
    protected function setCapturedCards(array $cardsToCapture) {
        foreach ($cardsToCapture as $key => $cardCaptured) {
            $this->setCardCaptureStatus($cardCaptured);

            $cardCoordinates = $this->getCardCoordinatesInBoard($cardCaptured);
            $this->setCardInBoard($cardCaptured, $cardCoordinates);
        }
    }

    /**
     * Sets card places status true
     * 
     * @param array $cardData
     * @return void
     */
    protected function setCardPlacedStatus(array $cardData) 
    {
        foreach ($this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] as $key => &$userCardSelection) {
            foreach ($userCardSelection[self::NODE_CARDS] as $key => &$card) {
                if ($card[self::NODE_CARD_USER_CARD_ID] === $cardData[self::NODE_CARD][self::NODE_CARD_USER_CARD_ID]) {
                    $userCardSelection[self::NODE_LAST_CHANGE] = $this->setLastChange();
                    $card[self::NODE_CARD_PLACED] = true;
                    break;
                }
            }
        }
    }
    
     /**
     * Sets card capture status
     * 
     * @param array $cardData
     * @return void
     */
    protected function setCardCaptureStatus(array $cardData) 
    {
        foreach ($this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] as $key => &$userCardSelection) {
            foreach ($userCardSelection[self::NODE_CARDS] as $key => &$card) {
                if ($card[self::NODE_CARD_USER_CARD_ID] === $cardData[self::NODE_CARD][self::NODE_CARD_USER_CARD_ID]) {
                    $userCardSelection[self::NODE_LAST_CHANGE] = $this->setLastChange();
                    $card[self::NODE_CARD_CAPTURED] = $cardData[self::NODE_CARD][self::NODE_CARD_CAPTURED];
                    break;
                }
            }
        }
    }

    /**
     * Sets current turn's movement
     * 
     * @param array $cardInPlay
     * @param array $cardsCaptured
     * @param array $coordinates
     * @param array $combo
     */
    protected function setTurnMovement($cardInPlay, $cardsCaptured, $coordinates, $combo) 
    {
        $turnPosition = \count($this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_TURNS]) - 1;

        $cards = [];
        foreach ($cardsCaptured as $key => $card) {
            $cards[] = $card[self::NODE_CARD];
        }

        $turnMovement = [
            self::NODE_CARD => $cardInPlay[self::NODE_CARD],
            self::NODE_MOVEMENT_COORDINATES => $coordinates,
            self::NODE_MOVEMENT_CARDS_CAPTURED => $cards
        ];
        
        if ($combo) {
            $turnMovement[self::NODE_MOVEMENT_COMBO] = $combo;
        }

        $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_TURNS][$turnPosition][self::NODE_TURN_COMPLETED] = true;
        $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_TURNS][$turnPosition][self::NODE_TURN_MOVEMENT] = $turnMovement;
        $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_TURNS][$turnPosition][self::NODE_LAST_CHANGE] = $this->setLastChange();
    }

    /**
     * Gets the cards that have been captured by the played card
     * 
     * @param array $cardInPlay
     * @param array $coordinates
     * @param bool $special
     * 
     * @return array
     */
    protected function getCardsCaptured(array $cardInPlay, array $coordinates, $special = true): array 
    {
        $capturedCards = [];

        $boardNeighbours = $this->getBoardNeighbours($coordinates);
        if ($special) {
            $cardsCapturedBySpecialMove = $this->checkIfCardsCanBeCaptured($cardInPlay, $boardNeighbours, true);
            if (\count($cardsCapturedBySpecialMove) > 1) {
                $capturedCards[self::NODE_CAPTURED_SPECIAL] = $cardsCapturedBySpecialMove;
            }
        }

        if(!isset($capturedCards[self::NODE_CAPTURED_SPECIAL])) {
            $capturedCards[self::NODE_CAPTURED_NORMAL] = $this->checkIfCardsCanBeCaptured($cardInPlay, $boardNeighbours, false);
        }

        return $capturedCards;
    }

    /**
     * Check if the cards can be captured
     * 
     * @param array $cardInPlay
     * @param array $coordinates
     * @param bool special
     * 
     * @return array
     */
    protected function checkIfCardsCanBeCaptured(array $cardInPlay, array $boardNeighbours, $special): array 
    {
        $capturedCards = [];
        $same = true;
        $sum = null;
  
        foreach ($boardNeighbours as $relativePosition => $cardInBoard) {
            $doCapture = false;

            if (($cardInBoard[self::NODE_USER_ID] === $this->user->getId()) && !$cardInBoard[self::NODE_CARD][self::NODE_CARD_CAPTURED]) {
                continue;
            }
    
            if (($cardInBoard[self::NODE_USER_ID] !== $this->user->getId()) && $cardInBoard[self::NODE_CARD][self::NODE_CARD_CAPTURED]) {
                continue;
            }

            if ($special) {
                switch ($relativePosition) {
                    case self::NODE_RELATIVE_POSITION_TOP:
                        [$same, $sum] = $this->checkSpecial(
                            \hexdec($cardInPlay[self::NODE_CARD][self::NODE_RELATIVE_POSITION_TOP]), \hexdec($cardInBoard[self::NODE_CARD][self::NODE_RELATIVE_POSITION_BOTTOM]), $same, $sum);
                        if ($same || $sum) {
                            $doCapture = true;
                        }
                        break;
                    case self::NODE_RELATIVE_POSITION_RIGHT:
                        [$same, $sum] = $this->checkSpecial(
                            \hexdec($cardInPlay[self::NODE_CARD][self::NODE_RELATIVE_POSITION_RIGHT]), \hexdec($cardInBoard[self::NODE_CARD][self::NODE_RELATIVE_POSITION_LEFT]), $same, $sum);
                        if ($same || $sum) {
                            $doCapture = true;
                        }
                        break;
                    case self::NODE_RELATIVE_POSITION_BOTTOM:
                        [$same, $sum] = $this->checkSpecial(
                            \hexdec($cardInPlay[self::NODE_CARD][self::NODE_RELATIVE_POSITION_BOTTOM]), \hexdec($cardInBoard[self::NODE_CARD][self::NODE_RELATIVE_POSITION_TOP]), $same, $sum);
                        if ($same || $sum) {
                            $doCapture = true;
                        }
                        break;
                    case self::NODE_RELATIVE_POSITION_LEFT:
                        [$same, $sum] = $this->checkSpecial(
                            \hexdec($cardInPlay[self::NODE_CARD][self::NODE_RELATIVE_POSITION_LEFT]), \hexdec($cardInBoard[self::NODE_CARD][self::NODE_RELATIVE_POSITION_RIGHT]), $same, $sum);
                        if ($same || $sum) {
                            $doCapture = true;
                        }
                        break;
                }

                if ($same === false && $sum === false) {
                    $capturedCards = [];
                    break;
                } 
            } else {
                switch ($relativePosition) {
                    case self::NODE_RELATIVE_POSITION_TOP:
                        if (\hexdec($cardInPlay[self::NODE_CARD][self::NODE_RELATIVE_POSITION_TOP]) > \hexdec($cardInBoard[self::NODE_CARD][self::NODE_RELATIVE_POSITION_BOTTOM])) {
                            $doCapture = true;
                        }
                        break;
                    case self::NODE_RELATIVE_POSITION_RIGHT:
                        if (\hexdec($cardInPlay[self::NODE_CARD][self::NODE_RELATIVE_POSITION_RIGHT]) > \hexdec($cardInBoard[self::NODE_CARD][self::NODE_RELATIVE_POSITION_LEFT])) {
                            $doCapture = true;
                        }
                        break;
                    case self::NODE_RELATIVE_POSITION_BOTTOM:
                        if (\hexdec($cardInPlay[self::NODE_CARD][self::NODE_RELATIVE_POSITION_BOTTOM]) > \hexdec($cardInBoard[self::NODE_CARD][self::NODE_RELATIVE_POSITION_TOP])) {
                            $doCapture = true;
                        }
                        break;
                    case self::NODE_RELATIVE_POSITION_LEFT:
                        if (\hexdec($cardInPlay[self::NODE_CARD][self::NODE_RELATIVE_POSITION_LEFT]) > \hexdec($cardInBoard[self::NODE_CARD][self::NODE_RELATIVE_POSITION_RIGHT])) {
                            $doCapture = true;
                        }
                        break;
                }    
            }
  
            if($doCapture) {
                $cardInBoard[self::NODE_CARD][self::NODE_CARD_CAPTURED] = $cardInBoard[self::NODE_USER_ID] !== $this->user->getId() ? true : false;
                $capturedCards[] = $cardInBoard;
            }
        }

        if($same) {
            $this->specialMove = self::SPECIAL_MOVE_SAME;
        } else if($sum) {
            $this->specialMove = self::SPECIAL_MOVE_SUM;
        }

        return $capturedCards;
    }

    /**
     * Checks if there is a special move between cards
     * 
     * @param int $cardInPlayProperty
     * @param int $cardInBoardProperty
     * @param bool $currentSame
     * @param mixed $currentSum
     * 
     * @return array
     */
    protected function checkSpecial(int $cardInPlayProperty, int $cardInBoardProperty, bool $currentSame, $currentSum) 
    {
        if ($currentSame && $cardInPlayProperty !== $cardInBoardProperty) {
            $currentSame = false;
        } 

        if($currentSum !== false) {
            $sum = $cardInPlayProperty + $cardInBoardProperty;
            $currentSum = \is_null($currentSum) || $currentSum === $sum ? $sum : false;
        }

        return [$currentSame, $currentSum];
    }

    /**
     * Checks if the battle board is full
     * 
     * @return bool
     */
    protected function checkIfBattleBoardIsComplete() 
    {
        $fullPlaces = 0;
        $board = $this->battleData[self::NODE_PROGRESS][self::NODE_MAIN_BATTLE_FIELD][self::NODE_BATTLE_BOARD];

        foreach ($board as $row => $rowData) {
            foreach ($rowData as $field => $card) {
                if (!$card) {
                    break;
                }

                $fullPlaces++;
            }
        }

        return $fullPlaces === self::MAX_BATTLE_BOARD_FIELDS ? true : false;
    }

    /**
     * Gets coinThrowNode
     *
     * @param array $usersIds
     * @return array
     */
    protected function getCoinThrowNode(array $usersIds): array
    {
        $coinThrowNode = [];

        $randomNumber = \random_int(0, 1);
        $coinThrowNode[] = $this->getUserCoinThrowNode($usersIds, $randomNumber);

        $nextNumber = $randomNumber === 0 ? 1 : 0;
        $coinThrowNode[] = $this->getUserCoinThrowNode($usersIds, $nextNumber);

        return $coinThrowNode;
    }

    /**
     * Gets coinThrowNode
     *
     * @param array $usersIds
     * @param int $index
     *
     * @return array
     */
    protected function getUserCoinThrowNode(array $usersIds, int $index): array
    {
        return [
            self::NODE_USER_ID => $usersIds[$index],
            self::NODE_CHECKED => false,
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];
    }

    /**
     * Gets batteFieldBase nodes
     *
     */
    protected function setBattleFieldBase()
    {
        $this->battleData[self::NODE_PROGRESS][self::NODE_MAIN_BATTLE_FIELD] = [
            self::NODE_BATTLE_BOARD => [
                [new \stdClass, new \stdClass, new \stdClass],
                [new \stdClass, new \stdClass, new \stdClass],
                [new \stdClass, new \stdClass, new \stdClass]
            ],
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];

        $userId = $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_COIN_THROW][0][self::NODE_USER_ID];
        $this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_TURNS] = [
            $this->addNewTurn($userId)
        ];
    }

    /**
     * Adds a new turn
     *
     * @param int $userId
     * @param bool $firstTurn
     * 
     * @return void
     */
    protected function addNewTurn(int $userId): array
    {
        return [
            self::NODE_USER_ID => $userId,
            self::NODE_TURN_MOVEMENT => [],
            self::NODE_TURN_COMPLETED => false,
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];
    }

    /**
     * Set lastChange date
     *
     */
    protected function setLastChange()
    {
        $today = new \DateTime();

        return $today->format(self::FORMAT_DATE);
    }

    /**
     * Gets UsersNode
     *
     * @param array $dataHelper
     * @return array
     */
    protected function getUsersNode(array $dataHelper): array
    {
        $usersNode = [];

        $mainUserId = $dataHelper['User']->getId();

        foreach ($dataHelper['userRelations'] as $key => $user) {
            $usersNode[] = [
                'user' => $user->toArray(),
                'statusId' => $user->getId() == $mainUserId ? BattleUserStatusConstant::ACCEPTED : BattleUserStatusConstant::PENDING,
                self::NODE_LAST_CHANGE => $this->setLastChange()
            ];
        }

        return $usersNode;
    }

    /**
     * Get battle rivas from data
     *
     * @return array 
     */
    protected function getRivalsFromBattleData(): array
    {
        $rival = [];

        foreach ($this->battleData[self::NODE_USERS] as $key => $user) {
            if ($this->getLoggedUser()->getId() != $user['user']['id']) {
                $rival[] = $user['user'];
            }
        }

        return $rival;
    }

     /**
     * Gets a card data from userCardSelection
     * 
     * @param int $userCardId
     * 
     * @return array
     */
    protected function getCardSelectionByUserCardId(int $userCardId) {
        $cardData = [];

        foreach ($this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] as $key => $userCardSelection) {
            foreach ($userCardSelection[self::NODE_CARDS] as $key => $card) {
                if ($card[self::NODE_CARD_USER_CARD_ID] === $userCardId) {
                   $cardData = [
                       self::NODE_USER_ID => $userCardSelection[self::NODE_USER_ID],
                       self::NODE_CARD => $card
                   ];
                   break;
                }
            }
        }

        return $cardData;
    }

    /**
     * Gets the board's neighbours cards by coordinates
     * 
     * @param array $coordinates
     */
    protected function getBoardNeighbours($coordinates): array
    {
        $board = $this->battleData[self::NODE_PROGRESS][self::NODE_MAIN_BATTLE_FIELD][self::NODE_BATTLE_BOARD];
        $row = $coordinates[self::NODE_ROW];
        $field = $coordinates[self::NODE_FIELD];

        $boardNeighbours = [];
        if (isset($board[$row - 1][$field]) && $board[$row - 1][$field]) {
            $boardNeighbours[self::NODE_RELATIVE_POSITION_TOP] = $board[$row - 1][$field];
        }
        if (isset($board[$row][$field + 1]) && $board[$row][$field + 1]) {
            $boardNeighbours[self::NODE_RELATIVE_POSITION_RIGHT] = $board[$row][$field + 1];
        }
        if (isset($board[$row + 1][$field]) && $board[$row + 1][$field]) {
            $boardNeighbours[self::NODE_RELATIVE_POSITION_BOTTOM ] = $board[$row + 1][$field];
        }
        if (isset($board[$row][$field - 1]) && $board[$row][$field - 1]) {
            $boardNeighbours[self::NODE_RELATIVE_POSITION_LEFT] = $board[$row][$field - 1];
        }

        return $boardNeighbours;
    }

    /**
     * Sets placed card in the board
     *
     * @param array $card
     * @param array $coordinates
     * 
     * @return void
     */
    protected function setCardInBoard(array $card, array $coordinates)
    {
        $row = $coordinates[self::NODE_ROW];
        $field = $coordinates[self::NODE_FIELD];

        $cardInBoard = [
            self::NODE_USER_ID => $card[self::NODE_USER_ID],
            self::NODE_CARD => $card[self::NODE_CARD],
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];

        $this->battleData[self::NODE_PROGRESS][self::NODE_MAIN_BATTLE_FIELD][self::NODE_BATTLE_BOARD][$row][$field] = $cardInBoard;
        $this->battleData[self::NODE_PROGRESS][self::NODE_MAIN_BATTLE_FIELD][self::NODE_LAST_CHANGE] = $this->setLastChange();
    }

    /**
     * Gets card's coordinates in board
     * 
     * @param array $card
     * @return array
     */
    protected function getCardCoordinatesInBoard(array $card): array
    {
        $row = $fiel = null;
        $board = $this->battleData[self::NODE_PROGRESS][self::NODE_MAIN_BATTLE_FIELD][self::NODE_BATTLE_BOARD];

        foreach ($board as $rowIndex => $rowData) {
            foreach ($rowData as $fieldIndex => $cardInBoard) {
                if ($cardInBoard && ($cardInBoard[self::NODE_CARD][self::NODE_CARD_USER_CARD_ID] === $card[self::NODE_CARD][self::NODE_CARD_USER_CARD_ID])) {
                    $row = $rowIndex;
                    $field = $fieldIndex;
                    break;
                }
            }
        }

        return [
            self::NODE_ROW => $row,
            self::NODE_FIELD => $field
        ];
    }

    /**
     * Gets cards captured by the user id
     * 
     * @param int $userId
     * @return array
     */
    protected function getCardsCapturedByUserId($userId) 
    {
        $cards = [];

        foreach ($this->battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] as $key => $userCardSelection) {
            foreach ($userCardSelection[self::NODE_CARDS] as $key => $card) {
                if (($userCardSelection[self::NODE_USER_ID] === $userId) && !$card[self::NODE_CARD_CAPTURED]) {
                    $cards[] = $card;
                } else if(($userCardSelection[self::NODE_USER_ID] !== $userId) && $card[self::NODE_CARD_CAPTURED]) {
                    $cards[] = $card;
                }
            }
        }
        
        return $cards;
    }
}
