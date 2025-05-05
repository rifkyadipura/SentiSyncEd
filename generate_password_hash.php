<?php
// Generate password hashes for the initial users
$passwords = [
    'SuperAdmin123',
    'mahasiswa123',
    'dosen123'
];

echo "Generated password hashes:\n";
foreach ($passwords as $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Password: $password\nHash: $hash\n\n";
}
?>
