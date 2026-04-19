<?php

return [
    [
        'event_key' => 'lead.consultation_requested',
        'label' => 'Consultation Requested',
        'actions' => [
            [
                'action_key' => 'consultation.user_confirmation',
                'label' => 'User Confirmation',
            ],
            [
                'action_key' => 'consultation.admin_notification',
                'label' => 'Admin Notification',
            ],
        ],
    ],
    [
        'event_key' => 'lead.created',
        'label' => 'Lead Created',
        'actions' => [
            [
                'action_key' => 'lead.user_confirmation',
                'label' => 'User Confirmation',
            ],
            [
                'action_key' => 'lead.admin_notification',
                'label' => 'Admin Notification',
            ],
        ],
    ],
    [
        'event_key' => 'contact.requested',
        'label' => 'Contact Form Submitted',
        'actions' => [
            [
                'action_key' => 'contact.user_confirmation',
                'label' => 'User Confirmation',
            ],
            [
                'action_key' => 'contact.admin_notification',
                'label' => 'Admin Notification',
            ],
        ],
    ],
    [
        'event_key' => 'popup.submitted',
        'label' => 'Popup Submitted',
        'actions' => [
            [
                'action_key' => 'popup.user_confirmation',
                'label' => 'User Confirmation',
            ],
        ],
    ],
];
