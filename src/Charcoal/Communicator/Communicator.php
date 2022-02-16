<?php

namespace Charcoal\Communicator;

use Charcoal\Config\ConfigurableTrait;
use Charcoal\Factory\FactoryInterface;
use Charcoal\Translator\Translation;
use Charcoal\Translator\TranslatorAwareTrait;
use Charcoal\View\ViewableTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * The Communicator service handles all email notifications.
 */
class Communicator implements CommunicatorInterface
{
    use ConfigurableTrait;
    use TranslatorAwareTrait;
    use ViewableTrait;

    /**
     * To whom the email is destined.
     *
     * @var array|mixed $to
     */
    protected $to;

    /**
     * From whom the email is delivered.
     *
     * @var array|mixed $from
     */
    protected $from;

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
     * Store the factory instance.
     *
     * @var FactoryInterface
     */
    private $emailFactory;

    /**
     * Returns a new Communicator object.
     *
     * @param array $data Class dependencies.
     */
    public function __construct(array $data)
    {
        $this->setConfig($data['config']);
        $this->setEmailFactory($data['emailFactory']);
        $this->setTranslator($data['translator']);
        $this->setView($data['view']);
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
     * The default from email and name.
     *
     * @return array
     */
    protected function defaultFrom()
    {
        return [
            'from' => $this->config('email.default_from'),
        ];
    }

    /**
     * The default from email and name.
     *
     * @return array
     */
    protected function defaultTo()
    {
        return [
            'to' => [
                'name'  => '',
                'email' => '',
            ],
        ];
    }

    /**
     * Create an email and deliver it, according to a given channel & scenario.
     *
     * @param  string      $channelName  The channel identifier.
     * @param  string      $scenarioName The scenario identifier.
     * @param  array|mixed $templateData The email data.
     * @param  array|mixed $files        A list of files to attach.
     * @throws InvalidArgumentException If the template data is scalar.
     * @return boolean
     */
    public function send($scenarioName, $channelName, $templateData = [], array $files = [])
    {
        $scenario = $this->getScenario($scenarioName, $channelName);

        if (is_scalar($templateData)) {
            throw new InvalidArgumentException(sprintf(
                'The Template Data parameter cannot be scalar for [%s::send()] method',
                get_class($this)
            ));
        }

        $email       = $this->emailFactory()->create('email');
        $defaultFrom = $this->parseRecursiveTranslations($this->defaultFrom());
        $defaultTo   = $this->parseRecursiveTranslations($this->defaultTo());

        $languageData = [
            'template_data' => [
                'currentLanguage' => $this->translator()->getLocale(),
            ],
        ];
        $scenarioData = $this->parseRecursiveTranslations($scenario);
        $templateData = isset($templateData['template_data']) ? $templateData : [ 'template_data' => $templateData ];

        // Merge templateData and formData, which adds the later to the rendering context.
        $renderData = array_merge_recursive($templateData, [
            'form_data' => $this->formData(),
        ]);

        // Manages renderable data found in the scenario config
        array_walk_recursive($scenarioData, function (&$value, $key, $templateData) {
            if ($key === 'template_ident') {
                return;
            }

            if (is_string($value)) {
                $value = $this->view()->renderTemplate($value, $templateData);
            }
        }, $renderData);

        $data = array_merge_recursive(
            $defaultFrom,
            $defaultTo,
            $languageData,
            $scenarioData,
            $templateData
        );

        if ($this->from() && !is_scalar($this->from())) {
            $data['from'] = $this->from();
        }

        if ($this->to() && !is_scalar($this->to())) {
            $data['to'] = $this->to();
        }

        if (!empty($files)) {
            $data['attachments'] = $files;
        }

        $email->setData($data);

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

        $locales = $this->translator()->availableLocales();

        // Check for language keys
        $isTranslation = true;
        foreach ($translation as $key => $val) {
            if (!in_array($key, $locales)) {
                $isTranslation = false;
                break;
            }
        }

        if ($isTranslation) {
            return $this->translator()->translate($translation);
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
    public function to()
    {
        return $this->to;
    }

    /**
     * @param  array|mixed $to To whom the email is sent.
     * @return self
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function from()
    {
        return $this->from;
    }

    /**
     * @param  array|mixed $from From whom the email is sent.
     * @return self
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
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
     * Set an email model factory.
     *
     * @param  FactoryInterface $factory The factory to create emails.
     * @return void
     */
    protected function setEmailFactory(FactoryInterface $factory)
    {
        $this->emailFactory = $factory;
    }

    /**
     * Retrieve the email model factory.
     *
     * @throws RuntimeException If the model factory is missing.
     * @return FactoryInterface
     */
    protected function emailFactory()
    {
        if (!isset($this->emailFactory)) {
            throw new RuntimeException(sprintf(
                'Email Factory is not defined for [%s]',
                get_class($this)
            ));
        }

        return $this->emailFactory;
    }
}
