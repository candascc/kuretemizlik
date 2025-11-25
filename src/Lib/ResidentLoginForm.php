<?php

class ResidentLoginForm
{
    public string $email;
    public string $phoneRaw;
    public string $phoneNormalized;
    public string $channel;
    /** @var array<string,string> */
    public array $errors = [];

    private function __construct(string $email, string $phoneRaw, string $phoneNormalized, string $channel)
    {
        $this->email = $email;
        $this->phoneRaw = $phoneRaw;
        $this->phoneNormalized = $phoneNormalized;
        $this->channel = $channel;
    }

    /**
     * @return self
     */
    public static function fromArray(array $input): self
    {
        $email = trim((string)($input['email'] ?? ''));
        $phoneRaw = trim((string)($input['phone'] ?? ''));
        $phoneNormalized = Utils::normalizePhone($phoneRaw) ?? '';
        $channelInput = strtolower(trim((string)($input['channel'] ?? '')));

        $channel = in_array($channelInput, ['email', 'sms'], true) ? $channelInput : '';

        if ($channel === '') {
            if ($email !== '') {
                $channel = 'email';
            } elseif ($phoneNormalized !== '') {
                $channel = 'sms';
            }
        }

        if ($channel === '') {
            $channel = 'email';
        }

        $form = new self($email, $phoneRaw, $phoneNormalized, $channel);
        $form->validate();

        return $form;
    }

    private function validate(): void
    {
        if ($this->email === '' && $this->phoneNormalized === '') {
            $this->errors['contact'] = 'Lütfen e-posta adresi veya telefon numarası girin.';
        }

        if ($this->phoneRaw !== '' && $this->phoneNormalized === '') {
            $this->errors['phone'] = 'Lütfen geçerli bir telefon numarası girin.';
        }

        if ($this->channel === 'email') {
            if ($this->email === '') {
                $this->errors['email'] = 'E-posta doğrulaması için adres gerekli.';
            } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = 'Lütfen geçerli bir e-posta adresi girin.';
            }
        }

        if ($this->channel === 'sms') {
            if ($this->phoneNormalized === '') {
                $this->errors['phone'] = 'SMS doğrulaması için telefon numarası gerekli.';
            }
        }
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * @return array<string,mixed>
     */
    public function toViewData(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phoneRaw,
            'channel' => $this->channel,
            'errors' => $this->errors,
        ];
    }
}

