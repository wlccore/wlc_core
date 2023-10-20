<?php
namespace eGamings\WLC\Domain\Captcha;

class Record
{
    protected $id              = 0;
    protected $ip              = '';
    /** @var \DateTimeImmutable */
    protected $first_date      = null;
    /** @var \DateTimeImmutable */
    protected $last_date       = null;
    protected $count_last_hour = 0;
    protected $count_last_day  = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getFirstDate()
    {
        return $this->first_date;
    }

    /**
     * @param \DateTimeImmutable $first_date
     */
    public function setFirstDate(\DateTimeImmutable $first_date): void
    {
        $this->first_date = $first_date;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getLastDate()
    {
        return $this->last_date;
    }

    /**
     * @param \DateTimeImmutable $last_date
     */
    public function setLastDate(\DateTimeImmutable $last_date): void
    {
        $this->last_date = $last_date;
    }

    /**
     * @return int
     */
    public function getCountLastHour(): int
    {
        return $this->count_last_hour;
    }

    /**
     * @param int $count_last_hour
     */
    public function setCountLastHour(int $count_last_hour): void
    {
        $this->count_last_hour = $count_last_hour;
    }

    public function incCountLastHour(): void
    {
        ++$this->count_last_hour;
    }

    /**
     * @return int
     */
    public function getCountLastDay(): int
    {
        return $this->count_last_day;
    }

    /**
     * @param int $count_last_day
     */
    public function setCountLastDay(int $count_last_day): void
    {
        $this->count_last_day = $count_last_day;
    }

    public function incCountLastDay(): void
    {
        ++$this->count_last_day;
    }
}