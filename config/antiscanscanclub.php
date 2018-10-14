<?php

return [
    "list" => env("ASSC_LIST"),
    "options" => [
    	"return" => 403,
    	"expired" => 5, // remove IPs from blacklists in hour (e.g: 5 hours)
    ]
];