<?php

return [

    /*
    | The visitor's country when Cloudflare's CF-IPCountry header is missing,
    | e.g. "KR" to see the site as a visitor from South Korea does. Leave it
    | empty in production, where Cloudflare sends the real country.
    */
    'visitor_country' => env('NK_LENS_VISITOR_COUNTRY'),

];
