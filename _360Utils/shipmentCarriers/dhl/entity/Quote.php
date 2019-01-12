<?php 
namespace app\_360Utils\shipmentCarriers\dhl\entity;

class Quote{

    public $BkgDetails;
    public $From;
    public $To;
    public $Dutiable;

        function __construct() {
            $this->BkgDetails  = new BkgDetails();
            $this->From = new Country();
            $this->To = new Country();
            $this->Dutiable = new Dutiable();
        }
    }

    class BkgDetails{
        private $pieces;
        public $QtdShp;

        function __construct() {
            $this->pieces  = [];
            $this->QtdShp = new QtdShp();
            
        }

        public function addPiece(PieceType $piece){
            array_push($this->pieces,$piece);
        }
    }

    class PieceType{

        
    }

    class QtdShp{
        public $QtdShpExChrg;

        function __construct() {
            $this->QtdShpExChrg = new QtdShpExChrg();
        }


    }

    class QtdShpExChrg{

    }

    class Country{
        public $CountryCode;
        public $Postalcode;
        public $City;
    }


    class Dutiable{
        public $DeclaredValue;
        public $DeclaredCurrency;
    }

?>