<?php

namespace App\Services\WhatsApp\Contracts;

use App\Models\WhatsApp\WaAccount;

interface WhatsAppClientInterface
{
    /**
     * Send a text message.
     *
     * @param WaAccount $account
     * @param string $toE164 Recipient phone in E.164 format
     * @param string $text Message body
     * @param array $options Additional options
     * @return array Response with message_id
     */
    public function sendText(WaAccount $account, string $toE164, string $text, array $options = []): array;

    /**
     * Send a template message.
     *
     * @param WaAccount $account
     * @param string $toE164
     * @param string $templateName
     * @param string $languageCode
     * @param array $components Template components/parameters
     * @return array
     */
    public function sendTemplate(
        WaAccount $account,
        string $toE164,
        string $templateName,
        string $languageCode,
        array $components = []
    ): array;

    /**
     * Send interactive buttons.
     *
     * @param WaAccount $account
     * @param string $toE164
     * @param string $bodyText
     * @param array $buttons Array of buttons [{id, title}, ...]
     * @param string|null $headerText
     * @param string|null $footerText
     * @return array
     */
    public function sendInteractiveButtons(
        WaAccount $account,
        string $toE164,
        string $bodyText,
        array $buttons,
        ?string $headerText = null,
        ?string $footerText = null
    ): array;

    /**
     * Send interactive list.
     *
     * @param WaAccount $account
     * @param string $toE164
     * @param string $bodyText
     * @param string $buttonText
     * @param array $sections Array of sections with rows
     * @param string|null $headerText
     * @param string|null $footerText
     * @return array
     */
    public function sendInteractiveList(
        WaAccount $account,
        string $toE164,
        string $bodyText,
        string $buttonText,
        array $sections,
        ?string $headerText = null,
        ?string $footerText = null
    ): array;

    /**
     * Mark a message as read.
     *
     * @param WaAccount $account
     * @param string $messageId
     * @return array
     */
    public function markAsRead(WaAccount $account, string $messageId): array;
}
