<?php

return [
    'bases' => [
        'Office' => ['lat' => 4.788752785617674, 'lng' => 7.054497183648935],
        'Onne'   => ['lat' => 4.723816, 'lng' => 7.151618],
        // 👇 add this (use your correct coords)
        'GRA'    => ['lat' => 4.8255, 'lng' => 7.0135], // placeholder; replace with your canonical pair
        'GuestHouse' => ['lat' => 4.7952895, 'lng' => 7.03272793],
    ],

     'guest_houses' => [
        [
            'label' => 'Trans Amadi Gardens Estate',
            'lat'   => 4.7952895,
            'lng'   => 7.03272793,
        ],
    ],

    'country_suffix' => ', Nigeria',

    // see note #2 below
    'manager_email' => env('LOGISTICS_MANAGER_EMAIL', null),
];

