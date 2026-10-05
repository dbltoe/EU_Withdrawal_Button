<?php
/**
 * EU Withdrawal Button -- the parts every side of the plugin shares.
 *
 * Pure functions only: settings, the Article 11a labels, who sees the button,
 * the submission time, and composing the two emails. Nothing in here touches
 * the database, so the harnesses can exercise all of it without a store.
 *
 * Wording: every text constant has its English value in the storefront
 * language file, which is the single source. Zen Cart loads a plugin's
 * extra_definitions only from the folder of the session's language, with no
 * English fallback (ArraysLanguageLoader, every release 1.5.8 -> 3.0.0), so a
 * store in another language would otherwise have undefined constants (a fatal
 * error on PHP 8). text() falls back to the English file instead, and the two
 * legal labels fall back to the directive's own wording for the session's
 * language code before English.
 *
 * Source must parse on PHP 7.4 and stay deprecation-clean on 8.5.
 *
 * @package  EuWithdrawalButton
 * @license  GNU General Public License v2.0 (https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class EuWithdrawalCore
{
    public const VERSION = 'v1.1.0';

    /** zen_mail() module names: our own, so the email hooks touch only our mail. */
    public const MAIL_ACK = 'eu_withdrawal_ack';
    public const MAIL_NOTICE = 'eu_withdrawal_notice';

    /** "Never" in the datetime columns (MySQL strict mode refuses 0000-00-00). */
    public const NEVER = '0001-01-01 00:00:00';

    /**
     * Statement statuses. "held": the spam trap was filled, so nothing was
     * emailed and nothing written to the order until staff send the
     * acknowledgment from the admin.
     */
    public const STATUSES = ['held', 'received', 'in_progress', 'done'];

    /** Show Withdrawal Button To. */
    public const SHOW_ALL = 'All Visitors';
    public const SHOW_COUNTRIES = 'Selected Countries';

    /** The 27 EU countries plus Iceland, Liechtenstein and Norway (the EEA applies the same directive). */
    public const COUNTRIES_DEFAULT = 'AT,BE,BG,HR,CY,CZ,DK,EE,FI,FR,DE,GR,HU,IE,IT,LV,LT,LU,MT,NL,PL,PT,RO,SK,SI,ES,SE,IS,LI,NO';

    /**
     * Article 11a(1) and 11a(3): the withdrawal function's and the
     * confirmation function's labels, verbatim from each language's text of
     * Directive (EU) 2023/2673 (EUR-Lex, read 2026-10-05). Keyed by language
     * code, so they work whatever a store calls its language folder.
     */
    public const LEGAL_LABELS = [
        'en' => ['withdraw from contract here', 'confirm withdrawal'],
        'de' => ['Vertrag widerrufen', 'Widerruf bestätigen'],
        'es' => ['desistir del contrato aquí', 'confirmar desistimiento'],
        'fi' => ['Peruuta sopimus tästä', 'Vahvista peruuttaminen'],
        'fr' => ['renoncer au contrat ici', 'confirmer la rétractation'],
        'it' => ['recedere dal contratto qui', 'conferma recesso'],
        'nl' => ['hier de overeenkomst herroepen', 'herroeping bevestigen'],
    ];

    /** The label constants: never filled from the English file for another language. */
    public const LABEL_KEYS = ['EU_WITHDRAWAL_LABEL_LINK', 'EU_WITHDRAWAL_LABEL_CONFIRM'];

    /**
     * Settings that win over the store's configuration. Empty on a store; the
     * harnesses use it to try each setting both ways in one run.
     *
     * @var array<string,string>
     */
    public static $overrides = [];

    /** @var array<string,string>|null the English text, loaded once */
    private static $english = null;

    /** Define the plugin's table constant. Guarded: extra_datafiles load on both sides. */
    public static function defineTables(): void
    {
        if (!defined('TABLE_EU_WITHDRAWALS')) {
            define('TABLE_EU_WITHDRAWALS', (defined('DB_PREFIX') ? DB_PREFIX : '') . 'eu_withdrawals');
        }
    }

    /**
     * Run a SELECT past core's query cache. Zen Cart memoizes every SELECT for
     * the length of a request (QueryCache, every release 1.5.8 -> 3.0.0) and a
     * write doesn't clear it, so reading back a statement just saved would get
     * the stale answer. Every SELECT this plugin runs on data that can change
     * goes through here.
     */
    public static function fresh($db, string $sql)
    {
        return $db->Execute($sql, null, false, 0, true);
    }

    /**
     * Fire one of the plugin's own notifiers: the seams EU Withdrawal Button
     * Pro (or a store's own observer) attaches to. They're listed in
     * docs/CUSTOMIZING.md. Nothing listens unless something is installed, and
     * nothing here changes when nothing does.
     */
    public static function notify(string $eventID, $data = [], &$p2 = null, &$p3 = null, &$p4 = null): void
    {
        global $zco_notifier;
        if (isset($zco_notifier) && is_object($zco_notifier)) {
            $zco_notifier->notify($eventID, $data, $p2, $p3, $p4);
        }
    }

    /* ----------------------------------------------------------------- *
     * Settings
     * ----------------------------------------------------------------- */

    /** A setting's value, or the default when it isn't defined. */
    public static function setting(string $key, string $default = ''): string
    {
        if (array_key_exists($key, self::$overrides)) {
            return (string)self::$overrides[$key];
        }
        return defined($key) ? (string)constant($key) : $default;
    }

    public static function settingOn(string $key, bool $default = false): bool
    {
        return self::setting($key, $default ? 'true' : 'false') === 'true';
    }

    /** A whole-number setting held between $min and $max. */
    public static function settingInt(string $key, int $default, int $min, int $max): int
    {
        $raw = trim(self::setting($key, (string)$default));
        $n = preg_match('/^-?\d+$/', $raw) ? (int)$raw : $default;
        return max($min, min($max, $n));
    }

    public static function enabled(): bool
    {
        return self::settingOn('EU_WITHDRAWAL_STATUS', true);
    }

    /* ----------------------------------------------------------------- *
     * Wording
     * ----------------------------------------------------------------- */

    /** The English text, from the storefront language file (the single source). */
    public static function englishText(): array
    {
        if (self::$english === null) {
            $define = require dirname(__DIR__) . '/catalog/includes/languages/english/extra_definitions/lang.eu_withdrawal.php';
            self::$english = is_array($define) ? $define : [];
        }
        return self::$english;
    }

    /** A text constant: the session language's, else English. */
    public static function text(string $key): string
    {
        if (defined($key)) {
            return (string)constant($key);
        }
        $english = self::englishText();
        return (string)($english[$key] ?? $key);
    }

    /**
     * The two legal labels. A constant defined by the session language's own
     * file wins (a store or translator can word it, as the law allows "an
     * unambiguous corresponding formulation"); else the directive's wording for
     * the language code, capitalized for a button; else English.
     *
     * @param string $which 'link' or 'confirm'
     */
    public static function label(string $which, string $languageCode = ''): string
    {
        $key = $which === 'confirm' ? 'EU_WITHDRAWAL_LABEL_CONFIRM' : 'EU_WITHDRAWAL_LABEL_LINK';
        if (defined($key)) {
            return (string)constant($key);
        }
        $code = strtolower(substr(trim($languageCode), 0, 2));
        $i = $which === 'confirm' ? 1 : 0;
        if ($code !== 'en' && isset(self::LEGAL_LABELS[$code])) {
            return self::upperFirst(self::LEGAL_LABELS[$code][$i]);
        }
        return (string)self::englishText()[$key];
    }

    /** First letter capitalized, multibyte-safe where mbstring is there. */
    public static function upperFirst(string $s): string
    {
        if ($s === '') {
            return $s;
        }
        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8');
        }
        return ucfirst($s);
    }

    /**
     * At most $max characters, never cutting a UTF-8 character in half.
     * Zen Cart 1.5.8's installer doesn't require mbstring, so it's used only
     * when present.
     */
    public static function cut(string $s, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, $max, 'UTF-8');
        }
        if (strlen($s) <= $max) {
            return $s;
        }
        $s = substr($s, 0, $max);
        // Drop a trailing partial multibyte sequence.
        return preg_replace('/[\xC0-\xFF][\x80-\xBF]*$/', '', $s) ?? $s;
    }

    public static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, defined('CHARSET') ? CHARSET : 'UTF-8');
    }

    /* ----------------------------------------------------------------- *
     * Who sees the button
     * ----------------------------------------------------------------- */

    /** "at, DE ,xx1" -> ['AT', 'DE']: two-letter codes only. */
    public static function parseCountries(string $list): array
    {
        $out = [];
        foreach (explode(',', strtoupper($list)) as $code) {
            $code = trim($code);
            if (preg_match('/^[A-Z]{2}$/', $code) === 1 && !in_array($code, $out, true)) {
                $out[] = $code;
            }
        }
        return $out;
    }

    /**
     * The country code a server variable carries, or ''. The store names the
     * variable (Cloudflare's HTTP_CF_IPCOUNTRY by default); Cloudflare's XX
     * (unknown) and T1 (Tor) count as unknown. Nothing is looked up anywhere.
     */
    public static function countryFromServer(array $server, string $variable): string
    {
        $variable = trim($variable);
        if ($variable === '' || preg_match('/^[A-Z][A-Z0-9_]*$/', $variable) !== 1) {
            return '';
        }
        $code = strtoupper(trim((string)($server[$variable] ?? '')));
        return (preg_match('/^[A-Z]{2}$/', $code) === 1 && $code !== 'XX' && $code !== 'T1') ? $code : '';
    }

    /**
     * Whether the footer button shows. Hidden only in Selected Countries mode,
     * for a visitor whose country is known and not on the list: when in doubt,
     * show.
     */
    public static function buttonShows(string $mode, array $countries, string $country): bool
    {
        if ($mode !== self::SHOW_COUNTRIES || $country === '') {
            return true;
        }
        return in_array(strtoupper($country), $countries, true);
    }

    /* ----------------------------------------------------------------- *
     * Time
     * ----------------------------------------------------------------- */

    /** The Store Time Zone setting when it's a real zone, else PHP's. */
    public static function timezone(): string
    {
        $tz = trim(self::setting('EU_WITHDRAWAL_TIMEZONE', ''));
        if ($tz !== '' && in_array($tz, DateTimeZone::listIdentifiers(), true)) {
            return $tz;
        }
        return date_default_timezone_get();
    }

    /**
     * A UTC time as the customer reads it: "2026-10-05 08:15:56 CEST (UTC+02:00)".
     * Numeric, so it reads the same in every language.
     */
    public static function localTime(string $utc, string $timezone): string
    {
        try {
            $t = new DateTime($utc, new DateTimeZone('UTC'));
            $t->setTimezone(new DateTimeZone($timezone));
        } catch (Exception $e) {
            return $utc . ' UTC';
        }
        $abbr = $t->format('T');
        $offset = $t->format('P');
        if ($offset === '+00:00' && in_array($abbr, ['UTC', 'GMT', 'Z', '+00'], true)) {
            return $t->format('Y-m-d H:i:s') . ' UTC';
        }
        // Zones with no abbreviation give "+02"; show the offset alone then.
        $name = preg_match('/^[+-]\d/', $abbr) === 1 ? '' : $abbr . ' ';
        return $t->format('Y-m-d H:i:s') . ' ' . $name . '(UTC' . $offset . ')';
    }

    /* ----------------------------------------------------------------- *
     * The statement and the emails
     * ----------------------------------------------------------------- */

    public static function reference(int $id): string
    {
        return 'W' . $id;
    }

    /** The order as the customer gave it: "#29" for a matched order, else what they typed. */
    public static function orderForCustomer(array $s): string
    {
        if ((int)($s['matched'] ?? 0) === 1 && (int)($s['orders_id'] ?? 0) > 0) {
            return '#' . (int)$s['orders_id'];
        }
        return trim((string)($s['order_entered'] ?? ''));
    }

    /**
     * What's withdrawn, as it's saved: the lines an add-on listed (Pro's item
     * picker passes them through NOTIFY_EU_WITHDRAWAL_FORM_READ), then what
     * the customer typed. Both are part of the statement, so both go into the
     * acknowledgment. '' means the whole order.
     *
     * @param mixed $lines the add-on's 'items_lines', if any
     */
    public static function combineItems($lines, string $typed): string
    {
        $out = [];
        foreach (is_array($lines) ? $lines : [] as $line) {
            $line = trim(is_scalar($line) ? (string)$line : '');
            if ($line !== '') {
                $out[] = self::cut($line, 255);
            }
        }
        $typed = trim($typed);
        if ($typed !== '') {
            $out[] = $typed;
        }
        return self::cut(implode("\n", $out), 6000);
    }

    /** What's withdrawn: the customer's list, or "The whole order". */
    public static function itemsText(array $s): string
    {
        $items = trim((string)($s['items_text'] ?? ''));
        return $items !== '' ? $items : self::text('EU_WITHDRAWAL_WHOLE_ORDER');
    }

    /** The staff note for the order's history: hidden from the customer. */
    public static function staffNote(array $s): string
    {
        $items = trim((string)($s['items_text'] ?? ''));
        return sprintf(
            self::text('EU_WITHDRAWAL_STAFF_NOTE'),
            (string)$s['created_local'],
            self::reference((int)$s['eu_withdrawals_id']),
            $items !== '' ? sprintf(self::text('EU_WITHDRAWAL_STAFF_NOTE_ITEMS'), $items) : self::text('EU_WITHDRAWAL_STAFF_NOTE_WHOLE')
        );
    }

    /** The optional line the customer sees on their order page. */
    public static function customerNote(array $s): string
    {
        return sprintf(self::text('EU_WITHDRAWAL_CUSTOMER_NOTE'), (string)$s['created_local']);
    }

    /**
     * Article 11a(4): the acknowledgment of receipt, "including its content and
     * the date and time of its submission". Receipt only, never a decision.
     *
     * @param array $s a statement row (eu_withdrawals_id, name, email, order_entered, orders_id, matched, items_text, created_local)
     * @return array{subject:string, text:string, html:string}
     */
    public static function composeAcknowledgment(array $s): array
    {
        $ref = self::reference((int)$s['eu_withdrawals_id']);
        $rows = [
            self::text('EU_WITHDRAWAL_FIELD_REFERENCE') => $ref,
            self::text('EU_WITHDRAWAL_FIELD_NAME') => (string)$s['name'],
            self::text('EU_WITHDRAWAL_FIELD_ORDER') => self::orderForCustomer($s),
            self::text('EU_WITHDRAWAL_FIELD_ITEMS') => self::itemsText($s),
            self::text('EU_WITHDRAWAL_FIELD_EMAIL') => (string)$s['email'],
            self::text('EU_WITHDRAWAL_FIELD_SUBMITTED') => (string)$s['created_local'],
        ];
        $intro = self::text('EU_WITHDRAWAL_ACK_INTRO');
        $receipt = self::text('EU_WITHDRAWAL_ACK_RECEIPT_ONLY');
        $heading = self::text('EU_WITHDRAWAL_ACK_HEADING');
        // Core's STORE_NAME_ADDRESS already starts with the store's name, so the
        // name isn't printed separately (spike S3 printed it twice).
        $address = defined('STORE_NAME_ADDRESS') ? trim((string)STORE_NAME_ADDRESS) : '';

        $text = $intro . "\n\n" . $receipt . "\n\n" . $heading . "\n";
        $html = '<p>' . self::esc($intro) . '</p><p>' . self::esc($receipt) . '</p>'
            . '<p><strong>' . self::esc($heading) . '</strong></p>'
            . '<table role="presentation" style="border-collapse:collapse;margin:0 0 12px">';
        foreach ($rows as $label => $value) {
            $text .= $label . ': ' . $value . "\n";
            $html .= '<tr><th scope="row" style="text-align:left;vertical-align:top;padding:4px 16px 4px 0">' . self::esc($label) . '</th>'
                . '<td style="vertical-align:top;padding:4px 0">' . nl2br(self::esc($value)) . '</td></tr>';
        }
        $html .= '</table>';
        if ($address !== '') {
            $text .= "\n" . $address . "\n";
            $html .= '<p style="color:#555;font-size:90%">' . nl2br(self::esc($address)) . '</p>';
        }
        return [
            'subject' => sprintf(self::text('EU_WITHDRAWAL_ACK_SUBJECT'), $ref),
            'text' => $text,
            'html' => $html,
        ];
    }

    /**
     * The store's notice: everything the customer sent, whether it matched an
     * order, and whether it's held. No admin link: the storefront doesn't know
     * the admin folder's name, and shouldn't.
     *
     * @return array{subject:string, text:string, html:string}
     */
    public static function composeStoreNotice(array $s): array
    {
        $ref = self::reference((int)$s['eu_withdrawals_id']);
        $matched = (int)($s['matched'] ?? 0) === 1 && (int)($s['orders_id'] ?? 0) > 0;
        $held = (string)($s['status'] ?? '') === 'held';
        $subject = $matched
            ? sprintf(self::text('EU_WITHDRAWAL_NOTICE_SUBJECT_ORDER'), $ref, (int)$s['orders_id'])
            : sprintf(self::text('EU_WITHDRAWAL_NOTICE_SUBJECT_UNMATCHED'), $ref);
        if ($held) {
            $subject .= ' ' . self::text('EU_WITHDRAWAL_NOTICE_SUBJECT_HELD');
        }
        $lines = [self::text('EU_WITHDRAWAL_NOTICE_INTRO')];
        $lines[] = $matched
            ? sprintf(self::text('EU_WITHDRAWAL_NOTICE_MATCHED'), (int)$s['orders_id'])
            : sprintf(self::text('EU_WITHDRAWAL_NOTICE_UNMATCHED'), trim((string)$s['order_entered']));
        $lines[] = $held ? self::text('EU_WITHDRAWAL_NOTICE_HELD') : self::text('EU_WITHDRAWAL_NOTICE_ACK_SENT');
        $lines[] = self::text('EU_WITHDRAWAL_NOTICE_NEXT');
        $rows = [
            self::text('EU_WITHDRAWAL_FIELD_REFERENCE') => $ref,
            self::text('EU_WITHDRAWAL_FIELD_NAME') => (string)$s['name'],
            self::text('EU_WITHDRAWAL_FIELD_EMAIL') => (string)$s['email'],
            self::text('EU_WITHDRAWAL_FIELD_ORDER') => trim((string)$s['order_entered']) !== '' ? trim((string)$s['order_entered']) : '#' . (int)$s['orders_id'],
            self::text('EU_WITHDRAWAL_FIELD_ITEMS') => self::itemsText($s),
            self::text('EU_WITHDRAWAL_FIELD_SUBMITTED') => (string)$s['created_local'],
        ];
        $text = implode("\n\n", $lines) . "\n\n";
        $html = '';
        foreach ($lines as $line) {
            $html .= '<p>' . self::esc($line) . '</p>';
        }
        $html .= '<table role="presentation" style="border-collapse:collapse;margin:0 0 12px">';
        foreach ($rows as $label => $value) {
            $text .= $label . ': ' . $value . "\n";
            $html .= '<tr><th scope="row" style="text-align:left;vertical-align:top;padding:4px 16px 4px 0">' . self::esc($label) . '</th>'
                . '<td style="vertical-align:top;padding:4px 0">' . nl2br(self::esc($value)) . '</td></tr>';
        }
        $html .= '</table>';
        return ['subject' => $subject, 'text' => $text, 'html' => $html];
    }

    /* ----------------------------------------------------------------- *
     * The order confirmation email
     * ----------------------------------------------------------------- */

    /**
     * Whether the order confirmation email gets the withdrawal link: the same
     * rule as the button, by the order's delivery country, else its billing
     * country (a virtual order has no delivery). Unknown shows it.
     */
    public static function orderEmailShows(string $mode, array $countries, string $deliveryIso, string $billingIso): bool
    {
        $country = strtoupper(trim($deliveryIso)) !== '' ? strtoupper(trim($deliveryIso)) : strtoupper(trim($billingIso));
        return self::buttonShows($mode, $countries, preg_match('/^[A-Z]{2}$/', $country) === 1 ? $country : '');
    }

    /**
     * The link for the order confirmation email. $href is core's
     * zen_href_link() result, which is already HTML-ready (& as &amp;): the
     * HTML part prints it as it comes, the text part gets the plain URL.
     *
     * @return array{text:string, html:string}
     */
    public static function orderEmailLink(string $href, string $label): array
    {
        $intro = self::text('EU_WITHDRAWAL_ORDER_EMAIL_INTRO');
        return [
            'text' => $intro . "\n" . $label . ': ' . str_replace('&amp;', '&', $href),
            'html' => '<p>' . self::esc($intro) . ' <a href="' . $href . '">' . self::esc($label) . '</a></p>',
        ];
    }

    /**
     * The text part with the link added before core's disclaimer and
     * copyright lines (each starts "\n-----\n"; the order's own separators are
     * longer), or at the end when the store has neither.
     */
    public static function insertBeforeFooter(string $email, string $add): string
    {
        $at = strpos($email, "\n-----\n");
        if ($at === false) {
            return rtrim($email, "\n") . "\n\n" . $add . "\n";
        }
        return substr($email, 0, $at) . "\n" . $add . "\n" . substr($email, $at);
    }

    /**
     * NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT: an address that isn't a customer
     * (a guest) has no format, and core sends those text-only. Our
     * acknowledgment goes as HTML to them; a customer's own TEXT choice is kept.
     */
    public static function mailFormat(&$format, $module): void
    {
        if (($module === self::MAIL_ACK || $module === self::MAIL_NOTICE) && (string)$format === '') {
            $format = 'HTML';
        }
    }

    /* ----------------------------------------------------------------- *
     * The footer button
     * ----------------------------------------------------------------- */

    /**
     * The button. $where is 'footer' (inside the footer, 2.1.0+) or 'end'
     * (before </body>, every version); the 'end' copy moves itself up under
     * the footer links when the page has them (1.5.8 and 2.0 templates).
     * White on #0b3d91 is 10.04:1, on the #072a66 hover 13.71:1 (AAA). Hover
     * deepens the shadow only; nothing moves.
     */
    public static function buttonHtml(string $href, string $label, string $where): string
    {
        $where = strtolower(trim((string)$where));
        $where = trim(preg_replace('/[^a-z0-9_-]+/', '-', $where), '-');
        if ($where === '') {
            $where = 'footer';
        }
        $html = '<style>'
            . '.euw-wrap{text-align:center;margin:12px 0;clear:both}'
            . '.euw-wrap.euw-end{padding:12px 0}'
            . '.euw-btn{display:inline-block;background:#0b3d91;color:#fff !important;font-weight:700;padding:.5em 1.1em;border-radius:4px;text-decoration:none !important;line-height:1.4;font-size:1rem}'
            . '.euw-btn:hover,.euw-btn:focus{background:#072a66;color:#fff !important;box-shadow:0 2px 6px rgba(0,0,0,.35)}'
            . '.euw-btn:focus-visible{outline:3px solid #ffbf47;outline-offset:2px}'
            . '</style>'
            . '<div class="euw-wrap euw-' . $where . '" id="euw-withdrawal-' . $where . '">'
            . '<a class="euw-btn" href="' . $href . '">' . self::esc($label) . '</a>'
            . '</div>';
        if ($where === 'end') {
            $html .= '<script>(function(){var w=document.getElementById("euw-withdrawal-end"),n=document.getElementById("navSuppWrapper");'
                . 'if(w&&n&&n.parentNode){n.parentNode.insertBefore(w,n.nextSibling);}})();</script>';
        }
        return $html;
    }
}
