<?php

class Link{
    // private $webadress = "https://specificationsapp.netlify.app";
    private $webadress = "http://localhost:5173";

    public function getLink(){
        return $this->webadress;
    }
}
