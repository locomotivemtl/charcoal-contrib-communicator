<?php

declare(strict_types=1);

namespace Charcoal\Communicator;

/**
 * Describes a communicator instance.
 */
interface CommunicatorInterface
{
    /**
     * Adds one or more communication channels.
     *
     * This method will replace any previously defined channels.
     *
     * @param  array $channels A map of channel names and details.
     * @return self
     */
    public function addChannels(array $channels);

    /**
     * Adds a communication channel.
     *
     * This method will replace any previously defined channel.
     *
     * @param  string $name The channel name.
     * @param  array  $data The channel details.
     * @return self
     */
    public function addChannel($name, array $data);

    /**
     * Determines if a communication channel is defined.
     *
     * @param  string $name The channel name.
     * @return boolean
     */
    public function hasChannel($name);

    /**
     * Retrieves a communication channel from the available pool.
     *
     * @param  string $name The channel name.
     * @return array
     */
    public function getChannel($name);

    /**
     * Determines if a communication scenario is defined.
     *
     * @param  string $scenarioName The scenario name.
     * @param  string $channelName  The channel name.
     * @return boolean
     */
    public function hasScenario($scenarioName, $channelName);

    /**
     * Retrieves a scenario from a communication channel.
     *
     * @param  string $scenarioName The scenario name.
     * @param  string $channelName  The channel name.
     * @return array
     */
    public function getScenario($scenarioName, $channelName);

    /**
     * Prepare email data according to a given channel, scenario,
     * and custom data.
     *
     * @param  string $channelName  The channel name.
     * @param  string $scenarioName The scenario name.
     * @param  array  $customData   The email or template data.
     * @return array
     */
    public function prepare($scenarioName, $channelName, array $customData = []);

    /**
     * Create and prepare an email according to a given channel, scenario,
     * and custom data.
     *
     * @param  string $channelName  The channel name.
     * @param  string $scenarioName The scenario name.
     * @param  array  $customData   The email or template data.
     * @return Email
     */
    public function create($scenarioName, $channelName, array $customData = []);

    /**
     * Create and send an email according to a given channel, scenario,
     * and custom data.
     *
     * @param  string   $channelName  The channel name.
     * @param  string   $scenarioName The scenario name.
     * @param  array    $customData   The email and template data.
     * @throws InvalidArgumentException If the template data is scalar.
     * @return boolean
     */
    public function send($scenarioName, $channelName, array $customData = []);

    /**
     * @return array|mixed
     */
    public function to();

    /**
     * @param  array|mixed $to To whom the email is sent.
     * @return self
     */
    public function setTo($to);

    /**
     * @return array|mixed
     */
    public function from();

    /**
     * @param  array|mixed $from From whom the email is sent.
     * @return self
     */
    public function setFrom($from);

    /**
     * @return array|mixed
     */
    public function formData();

    /**
     * @param  array|mixed $data The form data submitted.
     * @return self
     */
    public function setFormData($data);
}
