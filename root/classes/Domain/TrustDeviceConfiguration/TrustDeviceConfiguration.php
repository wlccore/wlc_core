<?php
namespace eGamings\WLC\Domain\TrustDeviceConfiguration;

/**
 * Class TrustDeviceConfiguration
 * @package eGamings\WLC\Domain\TrustDeviceConfiguration
 * @codeCoverageIgnore 
 */
class TrustDeviceConfiguration implements \Serializable
{
    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $userEmail;

    /**
     * @var string
     */
    private $code;

    /**
     * @var \DateTime
     */
    private $time;

    public function __construct(int $userId, string $userEmail, string $code, \DateTime $time)
    {
        $this->userId = $userId;
        $this->userEmail = $userEmail;
        $this->code = $code;
        $this->time = $time;
    }

    public function serialize()
    {
        return serialize(['userId' => $this->userId, 'userEmail' => $this->userEmail, 'code' => $this->code, 'time' => $this->time]);
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        $this->userId = $data['userId'];
        $this->userEmail = $data['userEmail'];
        $this->code = $data['code'];
        $this->time = $data['time'];
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    /**
     * @return \DateTime
     */
    public function getTime(): \DateTime
    {
        return $this->time;
    }
}