<?php

namespace App\Services;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorPNG;

class DocumentGenerationService
{
    public function generateDeliveryDocument(Order $order, User $user)
    {
        // Générer les QR et codes-barres
        $qrCodeBase64 = $this->generateQrCode($order->id);
        $barcodeBase64 = $this->generateBarcode($order->id);

        // Données à passer à la vue PDF
        $data = [
            'order' => $order,
            'qrCode' => $qrCodeBase64,
            'barcode' => $barcodeBase64,
        ];

        // Générer le PDF à partir d'une vue Blade
        $pdf = Pdf::loadView('documents.delivery', $data);

        // Nom et chemin du fichier
        $filename = 'delivery-order-' . $order->id . '.pdf';
        $path = 'documents/' . $filename;

        // Sauvegarder le fichier PDF dans le stockage public
        Storage::disk('public')->put($path, $pdf->output());

        // Enregistrer dans la BDD
        $document = Document::create([
            'order_id' => $order->id,
            'file_path' => $path,
            'file_name' => $filename,
            'type' => 'delivery',
            'generated_by' => $user->id
        ]);

        return $document;
    }

    private function generateQrCode($orderId)
    {
        $trackingUrl = config('app.url') . '/tracking/' . $orderId;
        $qrcode = QrCode::format('png')
                        ->size(200)
                        ->margin(1)
                        ->generate($trackingUrl);

        return base64_encode($qrcode);
    }

    private function generateBarcode($orderId)
    {
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($orderId, $generator::TYPE_CODE_128);

        return base64_encode($barcode);
    }
}
