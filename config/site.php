<?php
/*
----------------------------------------------------
  Site settings file for global site settings
----------------------------------------------------
*/
  return [
    'globalSettings' => [
        'mapKey' => getenv('MAP_API_KEY'),
        'google_calendar_id' => getenv('GOOGLE_CALENDAR_ID'),
        'google_calendar_service_account_key' => getenv('GOOGLE_CALENDAR_SERVICE_ACCOUNT_KEY'),
    ]
  ];
