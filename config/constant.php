<?php

return [
      'csv_headers' => [
            'contract number' => 'contract_id',
            'expiration date' => 'expiry_date',
            'salesperson' => 'sales_person',
            'email of salesperson' => 'sales_person_email',
            'email responsible' => 'email_resposible',
            'customer' => 'customer_name',
            'details' => 'details',
            'customer number' => 'customer_number',
            'address' => 'address',
            'city' => 'city',
            'postal code' => 'postal_code',
            'telephone' => 'telephone',
            'reminder' => 'reminder',
            'reminder days' => 'reminder',
      ],
      'int_cols' => ['contract_id', 'customer_number', 'reminder']
];
