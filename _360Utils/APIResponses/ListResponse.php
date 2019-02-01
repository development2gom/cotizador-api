<?php

namespace app\_360Utils\APIResponses;

class ListResponse{

    public $responseCode = 0;
    public $operation = "";
    public $message = "";
    public $results = [];
    public $errors = [];
    public $count;
    public $page =1;
    public $maxPage = 1;
    public $pageSize = 1000;
    public $errorMessages = [];
}