<?php

namespace app\models;

class ListResponse{

    public $responseCode=0;
    public $operation = "";
    public $results;
    public $count;
    public $page =1;
    public $maxPage = 1;
    public $pageSize = 1000;
}