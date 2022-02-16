<?php

namespace Charcoal\Communicator;

/**
 * Describes a communicator instance.
 */
interface CommunicatorInterface
{
    /**
     * @param  string $ident  The identifier of the channel.
     * @param  array  $config The configset of the channel.
     * @return void
     */
    public function addChannel($ident, $config = []);

    /**
     * @param  string      $channel      The channel identifier.
     * @param  string      $scenario     The scenario identifier.
     * @param  array|mixed $templateData The email data.
     * @throws InvalidArgumentException If the template data is scalar.
     * @return boolean
     */
    public function send($scenario, $channel, $templateData = []);

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
