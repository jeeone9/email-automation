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
            'first reminder days' => 'reminder',
            'second reminder days' => 'reminder_two',
      ],
      'mandatory_fields' => ['contract_id', 'expiry_date', 'sales_person', 'sales_person_email', 'details'],
      'int_cols' => ['contract_id', 'customer_number', 'reminder', 'reminder_two']
];
