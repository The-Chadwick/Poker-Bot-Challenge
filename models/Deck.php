<?php

class Deck {
	
	//creates a deck of 52 cards
	private $cards = array();
	
	/* Card Constants */
	private static $suit = array("hearts","clubs","diamonds","spades");
	private static $cardRank = array("duece", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "jack", "queen", "king", "ace");
	private static $handRank = array("high card", "pair", "two pair", "three of a kind", "straight", "flush", "full house", "four of a kind", "straight flush", "royal flush");
	
	//board cards throughout the phases 0:preflop 1:flop 2:turn 3:river
	private $boardCards = array();
	private $gameState;
	
	//default constructor
	public function __construct(){
		for($i=0; $i<52; $i++){
			$this->cards[$i] = $i;
		}
		$this->shuffleDeck();
		$this->gameState = 0;
	}
	
	//--START DECK FUNCTINOS--
	//shuffle
	private function shuffleDeck(){
		shuffle($this->cards);
	}
	
	//deal single card
	private function dealCard(){
		return array_shift($this->cards);
	}
	
	//return string of deck minus board and player cards
	private function getDeck(){
		return implode(" | ", $this->cards);
	}
	
	//return the amount of cards used
	public function getCardsUsedCount(){
		return 52 - count($this->cards);
	}
	
	//return the amount of cards left in the deck
	public function getRemainingCardCount(){
		return count($this->cards);
	}
	
	//deal hole cards for individual player
	public function getHoleCards(){
		return array($this->dealCard(), $this->dealCard());
	}
	
	//update board cards based on game phase
	public function updateBoardCards(){
		if ($this->gameState < 4){
			switch ($this->gameState) {
				case 0:
					break;
				case 1:
					array_push($this->boardCards, $this->dealCard(), $this->dealCard(), $this->dealCard());
					break;
				case 2:
					array_push($this->boardCards, $this->dealCard());
					break;
				case 3:
					array_push($this->boardCards, $this->dealCard());				
			}
			$this->gameState++;
		} else {
			echo "THE BOARD IS ALREADY FULL!";
		}
	}
	
	//get current board cards
	public function getBoardCards(){
		return $this->boardCards;
	}
	//--END DECK FUNCTIONS--
	
	//--START CARD FUNCTIONS--
	//return numeric suit
	private function getNumericSuit($card){
		return intval($card/13);
	}
	
	//return rank of card 0-12 0 being Duece, 12 being Ace
	public function getRank($card){
		return $card%13;
	}
	
	//return the human readable card
	public function toStringCard($card){
		$stringCard = $this->cardRank[$this->getRank($card)] . " of " . $this->suit[$this->getNumericSuit($card)];
		return $stringCard;
	}
	
	//return array of human readable cards
	public function toStringArrayCards($cards){
		foreach($cards as &$value){
			$value = $this->toStringCard($value);
		}
		return $cards;
	}
	//--END CARD FUNCTIONS--
	
	//--START HAND COMPARISON FUNCTIONS--
	//return hand strength as numeric value. this will be evaluated
	//for each possible player hand. once the best player hand has
	//been found, it can be compared to another player's best hand.
	public function getHandStrength($fiveCards){
		$rankedFive = array();
		for($i=0; $i<5; $i++){
			$rankedFive[$i] = $this->getRank($fiveCards[$i]);
		}
		//for easy comparison, we give each five card hand a numeric value
		$numericHandStrength = 0;
		
		//look for pairs or better
		$countPair = array_count_values($rankedFive);
		//check for flush, strait, or high card ELSE handles pair, 2pair, trips, full house, and four of a kind
		if(sizeof($countPair) > 4){
			$isFlush = 0; $isStraight = 0; $topCardValue = 0;
			rsort($rankedFive);
			//check for strait and high card
			if($rankedFive[0] > 5){
				if($rankedFive[0] == 12 && $rankedFive[1] == 3 && ($rankedFive[1] - $rankedFive[4]) == 3){
					//check for the wheel
					$isStraight = 1;
					$topCardValue = 5;
				} elseif(($rankedFive[0]-$rankedFive[4] == 4)){
					//check for all other straits
					$isStraight = 1;
					$topCardValue = $rankedFive[0];
				} else {
					//check for high card
					$topCardValue = sprintf("%03d%02d%02d%02d%02d", $rankedFive[0], $rankedFive[1], $rankedFive[2], $rankedFive[3], $rankedFive[4]);
				}
			}
			//check for flush
			$suitFive = array();
			for($i=0; $i<5; $i++){
				$suitFive[$i] = $this->getNumericSuit($fiveCards[$i]);
			}
			if(sizeof(array_count_values($suitFive)) == 1){
				$isFlush = 1;
				if($isStraight == 0){
					$topCardValue = sprintf("%02d%02d%02d%02d%02d", $rankedFive[0], $rankedFive[1], $rankedFive[2], $rankedFive[3], $rankedFive[4]);
				}
			}
			//generate the numeric hand strength
			if($isFlush == 1 && $isStraight == 1 && $topCardValue == 12){
				//royal flush
				$numericHandStrength = 90000000000;
			} elseif($isFlush == 1 && $isStraight == 1){
				//straight flush
				$numericHandStrength = 80000000000 + $topCardValue;
			} elseif($isFlush == 1 && $isStraight == 0){
				//flush
				$numericHandStrength = 50000000000 + $topCardValue;
			} elseif($isFlush == 0 && $isStraight == 1){
				//straight
				$numericHandStrength = 40000000000 + $topCardValue;
			} else {
				//high card
				$numericHandStrength = $topCardValue;
			}
		} else {
			if(in_array(4, $countPair)){
				//check for quads
				$quadCard = array_search(4, $countPair);
				$highCard = array_search(1, $countPair);
				$combined = sprintf("%02d%02d", $quadCard, $highCard);
				$numericHandStrength = 70000000000 + $combined;
			} elseif(in_array(3, $countPair) && in_array(2, $countPair)){
				//check for full house
				$set = array_search(3, $countPair);
				$pair = array_search(2, $countPair);
				$combined = sprintf("%02d%02d", $set, $pair);
				$numericHandStrength = 60000000000 + $combined;
			} elseif(in_array(3, $countPair)){
				//check for set
				$set = array_search(3, $countPair);
				$highCard = array_search(1, $countPair); unset($countPair[$highCard]);
				$lowCard = array_search(1, $countPair);
				if($highCard < $lowCard){
					$temp = $highCard;
					$highCard = $lowCard;
					$lowCard = $temp;
				}
				$combined = sprintf("%02d%02d%02d", $set, $highCard, $lowCard);
				$numericHandStrength = 30000000000 + $combined;
			} elseif(sizeof($countPair) == 3){
				//check for two pair
				$pair1 = array_search(2, $countPair); unset($countPair[$pair1]);
				$pair2 = array_search(2, $countPair);
				if($pair1 < $pair2){
					$temp = $pair1;
					$pair1 = $pair2;
					$pair2 = $temp;
				}
				$kicker = array_search(1, $countPair);
				$combined = sprintf("%02d%02d%02d", $pair1, $pair2, $kicker);
				$numericHandStrength = 20000000000 + $combined;
			} elseif(sizeof($countPair) == 4){
				//check for pair
				$pair = array_search(2, $countPair);
				rsort($rankedFive);
				$delete = array_search($pair, $rankedFive);
				array_splice($rankedFive, $delete, 2);
				$combined = sprintf("%02d%02d%02d%02d", $pair, $rankedFive[0], $rankedFive[1], $rankedFive[2]);
				$numericHandStrength = 10000000000 + $combined;
			}
		}
		//return value for direct comparison
		return $numericHandStrength;
	}
	
	//return readable player hand strength
	public function toStringHandStrength($numericHandStrength){
		$numericHandRank = substr($numericHandStrength, 0, 1);
		return $this->handRank[$numericHandRank];
	}
	
	//runs all possible hands from two hole cards through getHandStrength()
	public function getBestHoldemHand($holeCards){
		$sevenCards = array_merge($this->boardCards, $holeCards);
		print_r($this->toStringArrayCards($sevenCards));
		echo "<br>";
		//get board strength for comparison
		$strength = $this->getHandStrength($this->boardCards);
		echo $strength;
		//compare each five card hand with the current best five card hand
		for($i=0; $i<6; $i++){
			for($j=0; $j<(6-$i); $j++){
				$fiveCard = $sevenCards;
				array_splice($fiveCard, $i, 1);
				array_splice($fiveCard, ($j + $i), 1);
				echo "<br>";
				print_r($this->toStringArrayCards($fiveCard));
				$fiveCardStrength = $this->getHandStrength($fiveCard);
				echo $fiveCardStrength . "<br>";
				if($fiveCardStrength > $strength) $strength = $fiveCardStrength;
			}
		}
		//return best hand strength
		return $strength;
	}
	//--END HAND COMPARISON FUNCTIONS--
	
}

echo 'test';
$test = new Deck;

$playerHoleCards = $test->getHoleCards();
print_r($playerHoleCards);
echo "<br>";
print_r($test->toStringArrayCards($playerHoleCards));
$test->updateBoardCards();
$test->updateBoardCards();
$test->updateBoardCards();
$test->updateBoardCards();
echo "<br>";
print_r($test->getBoardCards());
echo "<br/>";
print_r($test->toStringArrayCards($test->getBoardCards()));
echo "<br/>";
echo $test->getHandStrength($test->getBoardCards()) . "<br>";
$bestPlayerHand = $test->getBestHoldemHand($playerHoleCards);
echo $bestPlayerHand;

?>