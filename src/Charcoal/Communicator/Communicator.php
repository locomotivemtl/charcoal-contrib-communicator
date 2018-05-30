<?php

namespace Charcoal\Communicator;

use InvalidArgumentException;
use RuntimeException;

// From PSR-3
use Psr\Log\LoggerAwareTrait;

// From `charcoal-email`
use Charcoal\Email\EmailAwareTrait;

// From `charcoal-translator`
use Charcoal\Translator\TranslatorAwareTrait;

// From `charcoal-view`
use Charcoal\View\ViewableTrait;

// From `mcaskill\charcoal-support`
use Charcoal\Support\App\Template\DynamicAppConfigTrait;
use Charcoal\Support\App\Template\SupportTrait;
use Charcoal\Support\Email\ManufacturableEmailTrait;
use Charcoal\Support\Model\ManufacturableModelTrait;

/**
 * The Communicator service handles all email confirmations and notifications.
 *
 * ## Constructor dependencies
 *
 * Constructor dependencies are passed as an array of `key=>value` pair.
 * The required dependencies are:
 *
 * - `logger` A PSR3 logger instance
 * - `email/factory` A Email Factory instance
 * - `translator` A Translator instance
 * - `logger` A PSR3 logger instance
 */
class Communicator implements CommunicatorInterface
{
    use DynamicAppConfigTrait;
    use EmailAwareTrait;
    use LoggerAwareTrait;
    use ManufacturableEmailTrait;
    use ManufacturableModelTrait;
    use SupportTrait;
    use TranslatorAwareTrait;
    use ViewableTrait;

    /**
     * The available communication channels.
     *
     * @var array
     */
    private $channels = [];

    /**
     * The submitted form data.
     *
     * @var array
     */
    private $formData = [];

    /**
     * Returns a new Communicator object.
     *
     * @param array $data Class dependencies.
     */
    public function __construct(array $data)
    {
        $this->setModelFactory($data['model/factory']);
        $this->setAppConfig($data['config']);

        $this->setLogger($data['logger']);
        $this->setDebug($data['debug']);
        $this->setEmailFactory($data['email/factory']);
        $this->setTranslator($data['translator']);
        $this->setView($data['view']);
        $this->setBaseUrl($data['base-url']);
    }

    /**
     * Adds a communication channel.
     *
     * @param string $ident  The identifier of the channel.
     * @param array  $config The configset of the channel.
     * @return void
     */
    public function addChannel($ident, $config = [])
    {
        $this->channels[$ident] = $config;
    }

    /**
     * Retrieve a communication channel from the available pool.
     *
     * @param string $channel The channel identifier.
     * @throws RuntimeException If the channel is not found in the config.
     * @return array
     */
    protected function getChannel($ident)
    {
        if (in_array($ident, $this->channels)) {
            return $this->channels[$ident];
        } else {
            throw new RuntimeException(sprintf(
                'The "%s" channel does not exist.',
                $channel
            ));
        }
    }

    /**
     * Retrieve a scenario from a communication channel.
     *
     * @param string $scenario The scenario identifier.
     * @param string $channel  The channel identifier.
     * @throws RuntimeException If the scenario are not found in the config.
     * @return array
     */
    protected function getScenario($scenario, $channel)
    {
        $channel = $this->getChannel($channel);

        if (in_array($scenario, $channel)) {
            return $channel[$scenario];
        } else {
            throw new RuntimeException(sprintf(
                'The "%s" scenario does not exist for the given "%s" channel.',
                $scenario,
                $channel
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
            'from' => $this->appConfig('email.default_from')
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
                'email' => ''
            ]
        ];
    }

    /**
     * Create an email and deliver it, according to a given channel & scenario.
     *
     * @param string      $channel      The channel identifier.
     * @param string      $scenario     The scenario identifier.
     * @param array|mixed $templateData The email data.
     * @throws InvalidArgumentException If the template data is scalar.
     * @return boolean
     */
    public function send($scenario, $channel, $templateData = [])
    {
        $channel  = $this->getChannel($channel);
        $scenario = $this->getScenario($scenario, $channel);

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
                'currentLanguage' => $this->translator()->getLocale()
            ]
        ];
        $scenarioData = $this->parseRecursiveTranslations($scenario);
        $templateData = isset($templateData['template_data']) ? $templateData : [ 'template_data' => $templateData ];

        // Merge templateData and formData, which adds the later to the rendering context.
        $renderData = array_merge_recursive(
            $templateData,
            [ 'form_data' => $this->formData() ]
        );

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

        $email->setData($data);

        return $email->send();
    }

    /**
     * Look for translations in a dataset.
     *
     * @param mixed $translation An array to translate.
     * @return array|\Charcoal\Translator\Translation|string|null
     */
    protected function parseRecursiveTranslations($translation)
    {
        if (!is_array($translation)) {
            return $translation;
        }

        $locales = [ 'en', 'fr' ];

        // Check for language keys
        $isTranslation = true;
        foreach ($translation as $key => $val) {
            if (!in_array($key, $locales)) {
                $isTranslation = false;
                break;
            }
        }

        if ($isTranslation) {
            return (string)$this->translator()->translation($translation);
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
     * @param array|mixed $to To whom the email is sent.
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
     * @param array|mixed $from From whom the email is sent.
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
     * @param array|mixed $data The form data submitted.
     * @return self
     */
    public function setFormData($data)
    {
        $this->formData = $formData;

        return $this;
    }
}
