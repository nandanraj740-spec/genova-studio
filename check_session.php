<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response = [
        "authenticated" => false,
        "redirect" => "login.html"
    ];
} else {
    $response = [
        "authenticated" => true,
        "user" => [
            "username" => $_SESSION["username"],
            "email" => $_SESSION["email"]
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?> 