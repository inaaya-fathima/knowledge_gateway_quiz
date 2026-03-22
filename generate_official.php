<?php
require 'config/auth_helper.php';
$data = get_all_users();
if (empty($data['officials'])) {
    $data['officials'] = [
        [
            'username' => 'official',
            'password' => password_hash('official123', PASSWORD_BCRYPT),
            'role' => 'official',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    save_all_users($data);
    echo "Added official";
} else {
    echo "Official already exists";
}
?>
