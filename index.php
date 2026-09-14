<?php
// Inisialisasi variabel
$result = null;
$error = null;
$inputValue = '';
$inputType = 'readera';

// Proses form jika di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inputValue'], $_POST['inputType'])) {
    $inputValue = trim($_POST['inputValue']);
    $inputType = $_POST['inputType'];

    if ($inputValue !== '') {
        try {
            $decValue = 0;

            // 1. Standarisasi semua input menjadi Decimal utuh (Reader A)
            if ($inputType === 'readera') {
                $decValue = (int)$inputValue;
            } elseif ($inputType === 'hikvision') {
                $hikStr = str_pad($inputValue, 8, "0", STR_PAD_LEFT);
                $fc = (int)substr($hikStr, 0, 3);
                $card = (int)substr($hikStr, 3, 5);
                $decValue = ($fc << 16) + $card;
            } elseif ($inputType === 'hex') {
                $decValue = hexdec($inputValue);
            } elseif ($inputType === 'hikperson') {
                // Konversi Desimal 10 digit ke Hex, pastikan 8 karakter
                $hexInput = str_pad(base_convert($inputValue, 10, 16), 8, "0", STR_PAD_LEFT);
                // Balikkan urutan byte (Little-Endian)
                $revHex = substr($hexInput, 6, 2) . substr($hexInput, 4, 2) . substr($hexInput, 2, 2) . substr($hexInput, 0, 2);
                $decValue = hexdec($revHex);
            }

            // 2. Generate semua format output dari nilai Decimal (Reader A)
            $fc_out = $decValue >> 16;
            $card_out = $decValue & 65535;
            
            // Format output biasa
            $readerA_out = str_pad($decValue, 8, "0", STR_PAD_LEFT);
            $hikvision_out = sprintf("%03d%05d", $fc_out, $card_out);
            $hex_out = strtoupper(str_pad(dechex($decValue), 8, "0", STR_PAD_LEFT));

            // Generate format Hik Person (Byte-Reversed)
            $revHexOut = substr($hex_out, 6, 2) . substr($hex_out, 4, 2) . substr($hex_out, 2, 2) . substr($hex_out, 0, 2);
            $hikperson_out = str_pad(base_convert($revHexOut, 16, 10), 10, "0", STR_PAD_LEFT);

            // Simpan hasil ke array
            $result = [
                'readera'   => $readerA_out,
                'hikvision' => $hikvision_out,
                'hex'       => $hex_out,
                'hikperson' => $hikperson_out,
                'fc'        => $fc_out,
                'card'      => $card_out
            ];
        } catch (Exception $e) {
            $error = "Terjadi kesalahan saat memproses data.";
        }
    } else {
        $error = "Kolom input tidak boleh kosong.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Format Converter</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4 text-gray-800">

    <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 w-full max-w-xl">
        <h1 class="text-2xl font-bold text-center text-blue-600 mb-6">RFID Format Converter</h1>

        <!-- Form Input -->
        <form method="POST" action="" class="space-y-4">
            <div>
                <label for="inputType" class="block text-sm font-medium text-gray-700 mb-1">Pilih Format Input</label>
                <select name="inputType" id="inputType" class="w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="readera" <?= $inputType == 'readera' ? 'selected' : '' ?>>Reader A (Desimal Utuh)</option>
                    <option value="hikvision" <?= $inputType == 'hikvision' ? 'selected' : '' ?>>Hikvision 3D-5D (Wiegand 26-bit)</option>
                    <option value="hikperson" <?= $inputType == 'hikperson' ? 'selected' : '' ?>>Hik Person (10 Digit Decimal)</option>
                    <option value="hex" <?= $inputType == 'hex' ? 'selected' : '' ?>>Hexadecimal</option>
                </select>
            </div>

            <div>
                <label for="inputValue" class="block text-sm font-medium text-gray-700 mb-1">Masukkan Nilai Kartu</label>
                <input type="text" name="inputValue" id="inputValue" value="<?= htmlspecialchars($inputValue) ?>" placeholder="Contoh: 00548558" required class="w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500 font-mono">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                Konversi Data
            </button>
        </form>

        <!-- Pesan Error -->
        <?php if ($error): ?>
            <div class="mt-4 p-3 bg-red-100 text-red-700 rounded border border-red-200">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Hasil Konversi -->
        <?php if ($result): ?>
            <div class="mt-8 border-t pt-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">Hasil Konversi</h2>
                
                <!-- Box Hik Person -->
                <div class="bg-indigo-50 p-3 rounded border border-indigo-100 flex justify-between items-center">
                    <div>
                        <span class="text-xs text-indigo-500 uppercase font-bold tracking-wider">Hik Person (10 Digit)</span>
                        <div class="text-lg font-mono font-semibold text-indigo-900"><?= $result['hikperson'] ?></div>
                    </div>
                </div>

                <!-- Box Hikvision -->
                <div class="bg-blue-50 p-3 rounded border border-blue-100 flex justify-between items-center">
                    <div>
                        <span class="text-xs text-blue-500 uppercase font-bold tracking-wider">Hikvision (3D-5D)</span>
                        <div class="text-lg font-mono font-semibold text-blue-900"><?= $result['hikvision'] ?></div>
                        <div class="text-xs text-blue-600 mt-1">
                            Facility Code: <strong><?= $result['fc'] ?></strong> &nbsp;|&nbsp; 
                            Card Number: <strong><?= $result['card'] ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Box Reader A -->
                <div class="bg-gray-50 p-3 rounded border flex justify-between items-center">
                    <div>
                        <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Reader A (Desimal)</span>
                        <div class="text-lg font-mono font-semibold text-gray-900"><?= $result['readera'] ?></div>
                    </div>
                </div>

                <!-- Box Hex -->
                <div class="bg-gray-50 p-3 rounded border flex justify-between items-center">
                    <div>
                        <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Hexadecimal</span>
                        <div class="text-lg font-mono font-semibold text-gray-900"><?= $result['hex'] ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>