<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Setting;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Reminder Service
 *
 * Handles automatic invoice reminders for overdue and pending invoices.
 * Can be triggered via cron job or manually.
 */
class ReminderService
{
    private array $config;
    private TranslationService $translator;

    // Reminder schedule (days after due date)
    private array $reminderSchedule = [
        1 => 3,   // First reminder: 3 days after due date
        2 => 7,   // Second reminder: 7 days after due date
        3 => 14,  // Third reminder: 14 days after due date
        4 => 30,  // Final reminder: 30 days after due date
    ];

    public function __construct()
    {
        $this->config = [
            'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
            'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'InvoiceFlow',
        ];

        $this->translator = TranslationService::getInstance();
    }

    /**
     * Process all pending reminders
     *
     * @return array Summary of sent reminders
     */
    public function processReminders(): array
    {
        $summary = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'details' => [],
        ];

        // Get all overdue invoices that need reminders
        $invoices = $this->getInvoicesNeedingReminder();

        foreach ($invoices as $invoice) {
            $summary['processed']++;

            try {
                $result = $this->sendReminder($invoice);

                if ($result) {
                    $summary['sent']++;
                    $summary['details'][] = [
                        'invoice_id' => $invoice['id'],
                        'invoice_number' => $invoice['number'],
                        'reminder_count' => $invoice['reminder_count'] + 1,
                        'status' => 'sent',
                    ];
                } else {
                    $summary['failed']++;
                    $summary['details'][] = [
                        'invoice_id' => $invoice['id'],
                        'invoice_number' => $invoice['number'],
                        'status' => 'failed',
                        'error' => 'Failed to send email',
                    ];
                }
            } catch (\Exception $e) {
                $summary['failed']++;
                $summary['details'][] = [
                    'invoice_id' => $invoice['id'],
                    'invoice_number' => $invoice['number'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $summary;
    }

    /**
     * Get invoices that need a reminder
     */
    private function getInvoicesNeedingReminder(): array
    {
        $today = date('Y-m-d');

        // Get overdue invoices with their reminder status
        $sql = "SELECT i.*, c.company_name, c.email as client_email, c.contact_name,
                       u.email as user_email, u.name as user_name,
                       DATEDIFF(NOW(), i.due_date) as days_overdue
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                JOIN users u ON i.user_id = u.id
                WHERE i.status IN ('pending', 'overdue')
                AND i.due_date < ?
                AND (i.reminder_count < 4 OR i.reminder_count IS NULL)
                ORDER BY i.due_date ASC";

        $invoices = Database::fetchAll($sql, [$today]);

        // Filter to only include invoices that should receive a reminder today
        return array_filter($invoices, function ($invoice) {
            return $this->shouldSendReminder($invoice);
        });
    }

    /**
     * Check if an invoice should receive a reminder
     */
    private function shouldSendReminder(array $invoice): bool
    {
        $reminderCount = (int) ($invoice['reminder_count'] ?? 0);
        $daysOverdue = (int) $invoice['days_overdue'];
        $lastReminderAt = $invoice['last_reminder_at'];

        // Check if max reminders reached
        if ($reminderCount >= 4) {
            return false;
        }

        // Get the threshold for next reminder
        $nextReminder = $reminderCount + 1;
        $threshold = $this->reminderSchedule[$nextReminder] ?? 999;

        // Check if enough days have passed
        if ($daysOverdue < $threshold) {
            return false;
        }

        // Check if we already sent a reminder today
        if ($lastReminderAt && date('Y-m-d', strtotime($lastReminderAt)) === date('Y-m-d')) {
            return false;
        }

        return true;
    }

    /**
     * Send a reminder for a specific invoice
     */
    public function sendReminder(array $invoice): bool
    {
        $client = Client::find($invoice['client_id']);
        $settings = Setting::getForUser($invoice['user_id']);

        if (!$client || empty($client['email'])) {
            return false;
        }

        $reminderCount = (int) ($invoice['reminder_count'] ?? 0) + 1;
        $locale = $settings['language'] ?? 'fr';

        // Build email content
        $subject = $this->buildSubject($invoice, $reminderCount, $locale);
        $body = $this->buildBody($invoice, $client, $settings, $reminderCount, $locale);

        // Send email
        $sent = $this->sendEmail(
            $client['email'],
            $client['contact_name'] ?? $client['company_name'],
            $subject,
            $body,
            $settings
        );

        if ($sent) {
            // Update reminder status
            $this->updateReminderStatus($invoice['id'], $reminderCount);

            // Update invoice status to overdue if not already
            if ($invoice['status'] !== 'overdue') {
                Invoice::update($invoice['id'], ['status' => 'overdue']);
            }
        }

        return $sent;
    }

    /**
     * Build reminder email subject
     */
    private function buildSubject(array $invoice, int $reminderCount, string $locale): string
    {
        $this->translator->setLocale($locale);

        $prefixes = [
            1 => '',
            2 => '[2nd Reminder] ',
            3 => '[3rd Reminder] ',
            4 => '[URGENT] ',
        ];

        $prefix = $locale === 'fr'
            ? ['', '[2eme rappel] ', '[3eme rappel] ', '[URGENT] '][$reminderCount] ?? ''
            : $prefixes[$reminderCount] ?? '';

        return $prefix . $this->translator->get('emails.reminder_subject', [
            ':number' => $invoice['number'],
        ]);
    }

    /**
     * Build reminder email body
     */
    private function buildBody(array $invoice, array $client, ?array $settings, int $reminderCount, string $locale): string
    {
        $this->translator->setLocale($locale);

        $companyName = $settings['company_name'] ?? 'InvoiceFlow';
        $daysOverdue = (int) (new \DateTime())->diff(new \DateTime($invoice['due_date']))->days;

        $greeting = $locale === 'fr' ? 'Bonjour' : 'Hello';
        $clientName = $client['contact_name'] ?? $client['company_name'];

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1e293b; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8fafc; }
                .invoice-details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .amount { font-size: 24px; color: #1e293b; font-weight: bold; }
                .overdue { color: #dc2626; }
                .button { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
                .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$companyName}</h1>
                </div>
                <div class='content'>
                    <p>{$greeting} {$clientName},</p>";

        if ($locale === 'fr') {
            $urgencyMessages = [
                1 => "Nous vous rappelons que la facture ci-dessous est en attente de paiement.",
                2 => "Malgre notre precedent rappel, nous n'avons toujours pas recu le paiement de la facture ci-dessous.",
                3 => "Nous vous contactons une nouvelle fois concernant la facture impayee ci-dessous. Merci de regulariser cette situation rapidement.",
                4 => "<strong class='overdue'>URGENT :</strong> La facture ci-dessous est en retard de {$daysOverdue} jours. Nous vous prions de proceder au paiement dans les plus brefs delais pour eviter des frais supplementaires.",
            ];
        } else {
            $urgencyMessages = [
                1 => "This is a friendly reminder that the invoice below is pending payment.",
                2 => "Despite our previous reminder, we have not yet received payment for the invoice below.",
                3 => "We are reaching out again regarding the unpaid invoice below. Please settle this matter promptly.",
                4 => "<strong class='overdue'>URGENT:</strong> The invoice below is {$daysOverdue} days overdue. Please process the payment immediately to avoid additional charges.",
            ];
        }

        $html .= "<p>" . ($urgencyMessages[$reminderCount] ?? $urgencyMessages[1]) . "</p>";

        $html .= "
                    <div class='invoice-details'>
                        <p><strong>" . ($locale === 'fr' ? 'Numero de facture' : 'Invoice Number') . ":</strong> {$invoice['number']}</p>
                        <p><strong>" . ($locale === 'fr' ? 'Date d\'emission' : 'Issue Date') . ":</strong> {$invoice['issue_date']}</p>
                        <p><strong>" . ($locale === 'fr' ? 'Date d\'echeance' : 'Due Date') . ":</strong> <span class='overdue'>{$invoice['due_date']}</span></p>
                        <p><strong>" . ($locale === 'fr' ? 'Montant total' : 'Total Amount') . ":</strong></p>
                        <p class='amount'>" . number_format((float) $invoice['total_amount'], 2, ',', ' ') . " EUR</p>
                    </div>";

        if (!empty($settings['bank_iban'])) {
            $html .= "
                    <div class='invoice-details'>
                        <p><strong>" . ($locale === 'fr' ? 'Coordonnees bancaires' : 'Bank Details') . ":</strong></p>
                        <p>IBAN: {$settings['bank_iban']}</p>";
            if (!empty($settings['bank_bic'])) {
                $html .= "<p>BIC: {$settings['bank_bic']}</p>";
            }
            $html .= "</div>";
        }

        $html .= "
                    <p>" . ($locale === 'fr' ? 'Si vous avez deja effectue ce paiement, veuillez ignorer ce message.' : 'If you have already made this payment, please disregard this message.') . "</p>
                    <p>" . ($locale === 'fr' ? 'Pour toute question, n\'hesitez pas a nous contacter.' : 'If you have any questions, please don\'t hesitate to contact us.') . "</p>
                    <p>" . ($locale === 'fr' ? 'Cordialement,' : 'Best regards,') . "<br>{$companyName}</p>
                </div>
                <div class='footer'>
                    <p>{$companyName}</p>
                    <p>" . ($settings['company_address'] ?? '') . "</p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Send email using PHPMailer
     */
    private function sendEmail(string $to, string $toName, string $subject, string $body, ?array $settings): bool
    {
        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = $this->config['port'];
            $mail->CharSet = 'UTF-8';

            if (!empty($this->config['username'])) {
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'];
                $mail->SMTPSecure = $this->config['port'] === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Sender
            $fromEmail = $settings['company_email'] ?? $this->config['from_address'];
            $fromName = $settings['company_name'] ?? $this->config['from_name'];
            $mail->setFrom($fromEmail, $fromName);

            // Recipient
            $mail->addAddress($to, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $body));

            return $mail->send();
        } catch (\Exception $e) {
            error_log("Failed to send reminder email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update reminder status in database
     */
    private function updateReminderStatus(int $invoiceId, int $reminderCount): void
    {
        Database::query(
            "UPDATE invoices SET reminder_count = ?, last_reminder_at = NOW() WHERE id = ?",
            [$reminderCount, $invoiceId]
        );
    }

    /**
     * Get reminder history for an invoice
     */
    public function getReminderHistory(int $invoiceId): array
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return [];
        }

        return [
            'reminder_count' => (int) ($invoice['reminder_count'] ?? 0),
            'last_reminder_at' => $invoice['last_reminder_at'],
            'next_reminder_scheduled' => $this->getNextReminderDate($invoice),
        ];
    }

    /**
     * Calculate next reminder date
     */
    private function getNextReminderDate(array $invoice): ?string
    {
        $reminderCount = (int) ($invoice['reminder_count'] ?? 0);

        if ($reminderCount >= 4 || $invoice['status'] === 'paid') {
            return null;
        }

        $nextReminder = $reminderCount + 1;
        $daysAfterDue = $this->reminderSchedule[$nextReminder] ?? null;

        if ($daysAfterDue === null) {
            return null;
        }

        $dueDate = new \DateTime($invoice['due_date']);
        $dueDate->modify("+{$daysAfterDue} days");

        return $dueDate->format('Y-m-d');
    }

    /**
     * Manually trigger a reminder for a specific invoice
     */
    public function triggerManualReminder(int $invoiceId, int $userId): array
    {
        $invoice = Invoice::findForUser($invoiceId, $userId);

        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found'];
        }

        if ($invoice['status'] === 'paid') {
            return ['success' => false, 'message' => 'Cannot send reminder for paid invoice'];
        }

        // Get client info
        $client = Client::find($invoice['client_id']);
        $invoice['client_email'] = $client['email'] ?? '';
        $invoice['contact_name'] = $client['contact_name'] ?? '';
        $invoice['company_name'] = $client['company_name'] ?? '';

        $sent = $this->sendReminder($invoice);

        return [
            'success' => $sent,
            'message' => $sent ? 'Reminder sent successfully' : 'Failed to send reminder',
        ];
    }
}
