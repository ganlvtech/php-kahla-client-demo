<?php

/**
 * @link https://stackoverflow.com/questions/27677236/encryption-in-javascript-and-decryption-with-php/27678978#27678978
 */
function evpKDF($password, $salt, $keySize = 8, $ivSize = 4, $iterations = 1, $hashAlgorithm = "md5")
{
    $targetKeySize = $keySize + $ivSize;
    $derivedBytes = "";
    $numberOfDerivedWords = 0;
    $block = null;
    $hasher = hash_init($hashAlgorithm);
    while ($numberOfDerivedWords < $targetKeySize) {
        if ($block != null) {
            hash_update($hasher, $block);
        }
        hash_update($hasher, $password);
        hash_update($hasher, $salt);
        $block = hash_final($hasher, true);
        $hasher = hash_init($hashAlgorithm);

        // Iterations
        for ($i = 1; $i < $iterations; $i++) {
            hash_update($hasher, $block);
            $block = hash_final($hasher, true);
            $hasher = hash_init($hashAlgorithm);
        }

        $derivedBytes .= substr($block, 0, min(strlen($block), ($targetKeySize - $numberOfDerivedWords) * 4));

        $numberOfDerivedWords += strlen($block) / 4;
    }

    return [
        "key" => substr($derivedBytes, 0, $keySize * 4),
        "iv" => substr($derivedBytes, $keySize * 4, $ivSize * 4),
    ];
}

function decrypt($ciphertext, $password)
{
    $ciphertext = base64_decode($ciphertext);
    if (substr($ciphertext, 0, 8) != "Salted__") {
        return false;
    }
    $salt = substr($ciphertext, 8, 8);
    $encrypted = substr($ciphertext, 16);
    $keyAndIV = evpKDF($password, $salt);
    $content = openssl_decrypt($encrypted, "aes-256-cbc", $keyAndIV["key"], OPENSSL_RAW_DATA, $keyAndIV["iv"]);
    return $content;
}

function encrypt($content, $password)
{
    $salt = random_bytes(8);
    $keyAndIV = evpKDF($password, $salt);
    $encrypted = openssl_encrypt($content, "aes-256-cbc", $keyAndIV["key"], OPENSSL_RAW_DATA, $keyAndIV["iv"]);
    $ciphertext = 'Salted__' . $salt . $encrypted;
    $ciphertext = base64_encode($ciphertext);
    return $ciphertext;
}
