<?php
// 生成 RSA 密钥对
$res = openssl_pkey_new(array(
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
));
openssl_pkey_export($res, $privateKey);
$publicKey = openssl_pkey_get_details($res);
$publicKey = $publicKey["key"];

// 保存密钥对到文件（可选）
file_put_contents('RSA/private_key.pem', $privateKey);
file_put_contents('RSA/public_key.pem', $publicKey);
file_put_contents('RSA/key_time.pem', time());

echo "Keys generated and saved.\n";

// 要加密的数据
$data = "Hello, RSA!";

// 使用公钥加密数据
openssl_public_encrypt($data, $encryptedData, $publicKey);
$encodedEncryptedData = base64_encode($encryptedData);
echo "Encrypted data: " . $encodedEncryptedData . "\n";

// 使用私钥解密数据
$decodedEncryptedData = base64_decode($encodedEncryptedData);
openssl_private_decrypt($decodedEncryptedData, $decryptedData, $privateKey);
echo "Decrypted data: " . $decryptedData . "\n";
echo "time:".time();
?>
