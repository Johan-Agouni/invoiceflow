<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/mail.php';
    }

    public function send(string $to, string $subject, string $body, ?string $attachment = null): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->Port = $this->config['port'];
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom($this->config['from']['address'], $this->config['from']['name']);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $this->wrapInTemplate($body);
            $mail->AltBody = strip_tags($body);

            // Attachment
            if ($attachment && file_exists($attachment)) {
                $mail->addAttachment($attachment);
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function sendInvoice(array $invoice, array $settings, string $pdfPath): bool
    {
        $subject = "Facture {$invoice['number']} - {$settings['company_name']}";

        $body = "
            <p>Bonjour,</p>
            <p>Veuillez trouver ci-joint la facture <strong>{$invoice['number']}</strong> d'un montant de <strong>" . number_format($invoice['total_amount'], 2, ',', ' ') . " €</strong>.</p>
            <p><strong>Date d'échéance :</strong> " . date('d/m/Y', strtotime($invoice['due_date'])) . "</p>
            <p>Merci de votre confiance.</p>
            <p>Cordialement,<br>{$settings['company_name']}</p>
        ";

        return $this->send($invoice['client_email'], $subject, $body, $pdfPath);
    }

    public function sendQuote(array $quote, array $settings, string $pdfPath): bool
    {
        $subject = "Devis {$quote['number']} - {$settings['company_name']}";

        $body = "
            <p>Bonjour,</p>
            <p>Veuillez trouver ci-joint notre devis <strong>{$quote['number']}</strong> d'un montant de <strong>" . number_format($quote['total_amount'], 2, ',', ' ') . " €</strong>.</p>
            <p><strong>Valide jusqu'au :</strong> " . date('d/m/Y', strtotime($quote['valid_until'])) . "</p>
            <p>N'hésitez pas à nous contacter pour toute question.</p>
            <p>Cordialement,<br>{$settings['company_name']}</p>
        ";

        return $this->send($quote['client_email'], $subject, $body, $pdfPath);
    }

    public function sendReminder(array $invoice, array $settings, int $reminderLevel): bool
    {
        $subjects = [
            1 => "Rappel : Facture {$invoice['number']} - Échéance dépassée",
            2 => "Second rappel : Facture {$invoice['number']} en attente de règlement",
            3 => "Dernier rappel : Facture {$invoice['number']} - Action requise",
        ];

        $subject = $subjects[$reminderLevel] ?? $subjects[1];

        $daysOverdue = (int) ((time() - strtotime($invoice['due_date'])) / 86400);

        $body = "
            <p>Bonjour,</p>
            <p>Nous nous permettons de vous rappeler que la facture <strong>{$invoice['number']}</strong> d'un montant de <strong>" . number_format($invoice['total_amount'], 2, ',', ' ') . " €</strong> est en attente de règlement depuis <strong>{$daysOverdue} jours</strong>.</p>
            <p><strong>Date d'échéance :</strong> " . date('d/m/Y', strtotime($invoice['due_date'])) . "</p>
            <p>Nous vous remercions de bien vouloir procéder au règlement dans les meilleurs délais.</p>
            <p>Si vous avez déjà effectué le paiement, veuillez ignorer ce message.</p>
            <p>Cordialement,<br>{$settings['company_name']}</p>
        ";

        return $this->send($invoice['client_email'], $subject, $body);
    }

    private function wrapInTemplate(string $content): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                {$content}
            </div>
        </body>
        </html>
        ";
    }
}
