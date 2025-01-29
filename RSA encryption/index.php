<?php
// 公钥和私钥文件路径
$publicKeyPath = 'public_key.pem';
$privateKeyPath = 'private_key.pem';

// 加密函数，支持长文本
function rsaEncrypt($plaintext, $publicKeyPath) {
    $publicKeyContent = @file_get_contents($publicKeyPath);
    if ($publicKeyContent === false) {
        return "无法读取公钥文件: ". $publicKeyPath;
    }
    $publicKey = openssl_pkey_get_public($publicKeyContent);
    if (!$publicKey) {
        return "无法加载公钥: ". openssl_error_string();
    }

    $keyDetails = openssl_pkey_get_details($publicKey);
    $maxLength = ($keyDetails['bits'] / 8) - 11;

    $encryptedBlocks = [];
    for ($i = 0; $i < strlen($plaintext); $i += $maxLength) {
        $block = substr($plaintext, $i, $maxLength);
        $encrypted = '';
        $result = openssl_public_encrypt($block, $encrypted, $publicKey);
        if (!$result) {
            openssl_free_key($publicKey);
            return "加密失败: ". openssl_error_string();
        }
        $encryptedBlocks[] = base64_encode($encrypted);
    }
    openssl_free_key($publicKey);
    return implode('|', $encryptedBlocks);
}

// 解密函数，支持长文本
function rsaDecrypt($ciphertext, $privateKeyPath) {
    $privateKeyContent = @file_get_contents($privateKeyPath);
    if ($privateKeyContent === false) {
        return "无法读取私钥文件: ". $privateKeyPath;
    }
    $privateKey = openssl_pkey_get_private($privateKeyContent);
    if (!$privateKey) {
        return "无法加载私钥: ". openssl_error_string();
    }

    $encryptedBlocks = explode('|', $ciphertext);
    $decrypted = '';
    foreach ($encryptedBlocks as $block) {
        $ciphertextBlock = base64_decode($block);
        $blockDecrypted = '';
        $result = openssl_private_decrypt($ciphertextBlock, $blockDecrypted, $privateKey);
        if (!$result) {
            openssl_free_key($privateKey);
            return "解密失败: ". openssl_error_string();
        }
        $decrypted .= $blockDecrypted;
    }
    openssl_free_key($privateKey);
    return $decrypted;
}

// 处理用户输入
$result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $input = $_POST['input'];

    if ($action === 'encrypt') {
        $result = rsaEncrypt($input, $publicKeyPath);
    } elseif ($action === 'decrypt') {
        $result = rsaDecrypt($input, $privateKeyPath);
    } else {
        $result = "无效的操作类型";
    }
    // 对结果进行 HTML 实体编码
    $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSA 加密解密</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        form {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
            text-align: center;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
        }

        select,
        textarea {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            resize: vertical;
            transition: border-color 0.3s ease;
        }

        select:focus,
        textarea:focus {
            border-color: #007BFF;
            outline: none;
        }

        input[type="submit"] {
            background-color: #007BFF;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        #result {
            margin-top: 30px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
            text-align: left;
            white-space: pre-wrap;
            word-break: break-all;
            position: relative;
            border: 5px solid #ccc;
			border-style:solid;
        }

        #result::before {
            content: '操作结果：';
            font-weight: 600;
            color: #333;
            display: block;
            margin-bottom: 10px;
        }

        #copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        #copy-btn.copied {
            background-color: #218838;
        }
    </style>
</head>

<body>
    <h1 style="color: #333; margin-bottom: 30px;">RSA 加密解密</h1>
    <form method="post">
        <label for="action">操作类型:</label>
        <select name="action" id="action">
            <option value="encrypt">加密</option>
            <option value="decrypt">解密</option>
        </select>
        <br>
        <label for="input">输入内容:</label>
        <textarea name="input" id="input" rows="10" cols="50"></textarea>
        <br>
        <input type="submit" value="执行操作">
    </form>
    <?php if ($result): ?>
        <div id="result">
            <p style="border-style:solid;"><?php echo $result; ?></p>
            <button id="copy-btn" onclick="copyToClipboard(this)">复制到剪贴板</button>
        </div>
        <script>
            function copyToClipboard(button) {
                const resultText = document.querySelector('#result p').textContent;
                const tempInput = document.createElement('input');
                tempInput.value = resultText;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);

                button.textContent = '复制成功';
                button.classList.add('copied');

                setTimeout(() => {
                    button.textContent = '复制到剪贴板';
                    button.classList.remove('copied');
                }, 2000);
            }
        </script>
    <?php endif; ?>
</body>

</html>
