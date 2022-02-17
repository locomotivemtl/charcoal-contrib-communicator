<?php

declare(strict_types=1);

namespace Charcoal\Communicator;

use Charcoal\Email\EmailAwareTrait;
use Charcoal\Factory\FactoryInterface as Factory;
use Charcoal\Translator\Translation;
use Charcoal\Translator\Translator;
use Charcoal\View\ViewInterface as View;
use InvalidArgumentException;
use RuntimeException;

/**
 * The Communicator service handles all email notifications.
 */
class Communicator implements CommunicatorInterface
{
    use EmailAwareTrait {
        EmailAwareTrait::parseEmail as parseEmailToString;
    }

    /**
     * The sender email address.
     *
     * Expected to be one address.
     *
     * @var array<string, string>
     */
    private $from;

    /**
     * The recipient email address.
     *
     * Expected to be one or many addresses.
     *
     * @var array<string, string>[]
     */
    private $to = [];

    /**
     * The default sender email address.
     *
     * Expected to be one address.
     *
     * @var array<string, string>
     */
    private $defaultFrom;

    /**
     * The default recipient email address.
     *
     * Expected to be one or many addresses.
     *
     * @var array<string, string>[]
     */
    private $defaultTo = [];

    /**
     * The available communication channels.
     *
     * @var array<string, array<string, array>>
     */
    private $channels = [];

    /**
     * The submitted form data.
     *
     * @var array
     */
    private $formData = [];

    /**
     * The email factory instance.
     *
     * @var Factory
     */
    private $emailFactory;

    /**
     * The translator instance.
     *
     * @var Translator
     */
    private $translator;

    /**
     * The view renderer instance.
     *
     * @var View
     */
    private $view;

    /**
     * Returns a new Communicator instance.
     *
     * @param Factory    $emailFactory The email factory instance.
     * @param Translator $translator   The translator instance.
     * @param View       $view         The view renderer instance.
     */
    public function __construct(
        Factory $emailFactory,
        Translator $translator,
        View $view
    ) {
        $this->emailFactory = $emailFactory;
        $this->translator   = $translator;
        $this->view         = $view;
    }

    /**
     * Gets the email factory instance.
     *
     * @return Factory
     */
    public function getEmailFactory(): Factory
    {
        return $this->emailFactory;
    }

    /**
     * Gets the translator instance.
     *
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Gets the view renderer instance.
     *
     * @return View
     */
    public function getView(): View
    {
        return $this->view;
    }

    /**
     * Adds one or more communication channels.
     *
     * This method will replace any previously defined channels.
     *
     * @param  array $channels A map of channel names and details.
     * @return self
     */
    public function addChannels(array $channels)
    {
        foreach ($channels as $ident => $channel) {
            $this->addChannel($ident, $channel);
        }

        return $this;
    }

    /**
     * Adds a communication channel.
     *
     * This method will replace any previously defined channel.
     *
     * @param  string $name The channel name.
     * @param  array  $data The channel details.
     * @return self
     */
    public function addChannel($name, array $data)
    {
        $this->channels[$name] = $data;

        return $this;
    }

    /**
     * Determines if a communication channel is defined.
     *
     * @param  string $name The channel name.
     * @return boolean
     */
    public function hasChannel($name)
    {
        return isset($this->channels[$name]);
    }

    /**
     * Retrieves a communication channel from the available pool.
     *
     * @param  string $name The channel name.
     * @throws InvalidArgumentException If the channel is not defined.
     * @return array
     */
    public function getChannel($name)
    {
        if ($this->hasChannel($name)) {
            return $this->channels[$name];
        } else {
            throw new InvalidArgumentException(sprintf(
                'Communicator channel [%s] does not exist',
                $name
            ));
        }
    }

    /**
     * Determines if a communication scenario is defined.
     *
     * @param  string $scenarioName The scenario name.
     * @param  string $channelName  The channel name.
     * @return boolean
     */
    public function hasScenario($scenarioName, $channelName)
    {
        return isset($this->channels[$channelName][$scenarioName]);
    }

    /**
     * Retrieves a scenario from a communication channel.
     *
     * @param  string $scenarioName The scenario name.
     * @param  string $channelName  The channel name.
     * @throws InvalidArgumentException If the scenario or channel is not defined.
     * @return array
     */
    public function getScenario($scenarioName, $channelName)
    {
        if ($this->hasScenario($scenarioName, $channelName)) {
            return $this->channels[$channelName][$scenarioName];
        } else {
            throw new InvalidArgumentException(sprintf(
                'Communicator scenario [%s] does not exist on channel [%s]',
                $scenarioName,
                $channelName
            ));
        }
    }

    /**
     * Sets the default sender email address.
     *
     * @param  mixed $email An email address.
     * @return self
     */
    public function setDefaultFrom($email): self
    {
        $this->defaultFrom = $this->parseEmailToArray($email);

        return $this;
    }

    /**
     * Gets the default sender email address.
     *
     * @return array<string, string>|null
     */
    public function getDefaultFrom(): ?array
    {
        return $this->defaultFrom;
    }

    /**
     * Sets the default recipient email address.
     *
     * @param  mixed $emails One or many email addresses.
     * @throws InvalidArgumentException If the email addresses are invalid.
     * @return self
     */
    public function setDefaultTo($emails): self
    {
        $this->defaultTo = [];

        if (is_string($emails) || isset($emails['email'])) {
            $this->addTo($emails);

            return $this;
        }

        if (is_array($emails)) {
            foreach ($emails as $recipient) {
                $this->addTo($recipient);
            }

            return $this;
        }

        throw new InvalidArgumentException(
            'Expected one or many email addresses as strings or arrays'
        );
    }

    /**
     * Adds a default recipient email address.
     *
     * @param  mixed $email An email address.
     * @return self
     */
    public function addDefaultTo($email): self
    {
        $this->defaultTo[] = $this->parseEmailToArray($email);

        return $this;
    }

    /**
     * Gets the default recipient email addresses.
     *
     * @return array<string, string>[]
     */
    public function getDefaultTo(): array
    {
        return $this->defaultTo;
    }

    /**
     * Sets the sender email address.
     *
     * @param  mixed $email An email address.
     * @return self
     */
    public function setFrom($email): self
    {
        $this->from = $this->parseEmailToArray($email);

        return $this;
    }

    /**
     * Gets the sender email address.
     *
     * @return array<string, string>|null
     */
    public function getFrom(): ?array
    {
        return $this->from;
    }

    /**
     * Sets the recipient email addresses.
     *
     * @param  mixed $emails One or many email addresses.
     * @throws InvalidArgumentException If the email addresses are invalid.
     * @return self
     */
    public function setTo($emails): self
    {
        $this->to = [];

        if (is_string($emails) || isset($emails['email'])) {
            $this->addTo($emails);

            return $this;
        }

        if (is_array($emails)) {
            foreach ($emails as $recipient) {
                $this->addTo($recipient);
            }

            return $this;
        }

        throw new InvalidArgumentException(
            'Expected one or many email addresses as strings or arrays'
        );
    }

    /**
     * Adds a recipient email address.
     *
     * @param  mixed $email An email address.
     * @return self
     */
    public function addTo($email): self
    {
        $this->to[] = $this->parseEmailToArray($email);

        return $this;
    }

    /**
     * Gets the recipient email addresses.
     *
     * @return array<string, string>[]
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * Prepare email data according to a given channel, scenario,
     * and custom data.
     *
     * @param  string $channelName  The channel name.
     * @param  string $scenarioName The scenario name.
     * @param  array  $customData   The email or template data.
     *     If $emailData does not contain a "template_data" key,
     *     the method assumes is the contents of "template_data".
     * @param  array  $attachments  List of paths to attach to email.
     * @return array
     */
    public function prepare($scenarioName, $channelName, array $customData = [], array $attachments = [])
    {
        $scenarioData = $this->getScenario($scenarioName, $channelName);
        $scenarioData = $this->parseRecursiveTranslations($scenarioData);

        $defaultFrom = $this->parseRecursiveTranslations([
            'from' => $this->getDefaultFrom(),
        ]);
        $defaultTo = $this->parseRecursiveTranslations([
            'to' => $this->getDefaultTo(),
        ]);

        $languageData = [
            'template_data' => [
                'currentLanguage' => $this->getTranslator()->getLocale(),
            ],
        ];

        if (!isset($customData['template_data'])) {
            $customData = [
                'template_data' => $customData,
            ];
        }

        // Merge emailData and formData, which adds the later to the rendering context.
        $renderData = array_merge_recursive($customData, [
            'form_data' => $this->formData(),
        ]);

        // Manages renderable data found in the scenario config
        array_walk_recursive($scenarioData, function (&$value, $key, $renderData) {
            if ($key === 'template_ident') {
                return;
            }

            if (is_string($value)) {
                $value = $this->getView()->renderTemplate($value, $renderData);
            }
        }, $renderData);

        $emailData = array_merge_recursive(
            $defaultFrom,
            $defaultTo,
            $languageData,
            $scenarioData,
            $customData
        );

        if ($this->getFrom()) {
            $emailData['from'] = $this->getFrom();
        }

        if ($this->getTo()) {
            $emailData['to'] = $this->getTo();
        }

        if (!empty($attachments)) {
            $emailData['attachments'] = $attachments;
        }

        return $emailData;
    }

    /**
     * Create and prepare an email according to a given channel, scenario,
     * and custom data.
     *
     * @param  string $channelName  The channel name.
     * @param  string $scenarioName The scenario name.
     * @param  array  $customData   The email or template data.
     * @param  array  $attachments  List of paths to attach to email.
     * @return Email
     */
    public function create($scenarioName, $channelName, array $customData = [], array $attachments = [])
    {
        $data  = $this->prepare($scenarioName, $channelName, $customData, $attachments);
        $email = $this->getEmailFactory()->create('email')->setData($data);

        return $email;
    }

    /**
     * Create, prepare, and send an email according to a given channel, scenario,
     * and custom data.
     *
     * @param  string $channelName  The channel name.
     * @param  string $scenarioName The scenario name.
     * @param  array  $customData   The email or template data.
     * @param  array  $attachments  List of paths to attach to email.
     * @return boolean
     */
    public function send($scenarioName, $channelName, array $customData = [], array $attachments = [])
    {
        $email = $this->create($scenarioName, $channelName, $customData, $attachments);

        return $email->send();
    }

    /**
     * Look for translations in a dataset.
     *
     * @param  mixed $translation An array to translate.
     * @return array|Translation|string|null
     */
    protected function parseRecursiveTranslations($translation)
    {
        if (!is_array($translation)) {
            return $translation;
        }

        $locales = $this->getTranslator()->availableLocales();

        // Check for language keys
        $isTranslation = true;
        foreach ($translation as $key => $val) {
            if (!in_array($key, $locales)) {
                $isTranslation = false;
                break;
            }
        }

        if ($isTranslation) {
            return $this->getTranslator()->translate($translation);
        }

        $out = [];
        foreach ($translation as $key => $val) {
            $out[$key] = $this->parseRecursiveTranslations($val);
        }

        return $out;
    }

    /**
     * @return array|mixed
     */
    public function formData()
    {
        return $this->formData;
    }

    /**
     * @param  array|mixed $data The form data submitted.
     * @return self
     */
    public function setFormData($data)
    {
        $this->formData = $formData;

        return $this;
    }

    /**
     * @param  mixed $email An email address (either a string or an array).
     * @throws InvalidArgumentException If the email address is invalid.
     * @return array<string, string>
     */
    protected function parseEmailToArray($email): array
    {
        if (is_string($email)) {
            return $this->emailToArray($email);
        }

        if (is_array($email) && isset($email['email'])) {
            return $email;
        }

        throw new InvalidArgumentException(
            'Expected email address as a string or array'
        );
    }
}
