<?php

use App\Enums\NationalityType;
use App\Rules\NationalCode;
use App\Rules\NotIranianNationalCode;
use App\Rules\PersianName;
use App\Support\Digits;
use App\Support\PersianText;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('ثبت نام')]
class extends Component
{
    public string $first_name_fa = '';

    public string $last_name_fa = '';

    public NationalityType $nationality_type = NationalityType::Iranian;

    public string $identity = '';

    public string $mobile = '';

    // نگاشت نام فیلد → تابع نرمال‌ساز
    protected array $normalizers = [
        'first_name_fa' => [PersianText::class, 'name'],
        'last_name_fa' => [PersianText::class, 'name'],
        'identity' => [Digits::class, 'onlyDigits'],
        'mobile' => [Digits::class, 'onlyDigits'],
    ];

    // متد عمومی Livewire برای هر تغییر
    public function updated($property): void
    {
        $this->normalizeField($property);
    }
    // نرمال‌سازی یک فیلد مشخص
    protected function normalizeField(string $field): void
    {
        if (! isset($this->normalizers[$field])) {
            return;
        }
        $callable = $this->normalizers[$field];
        $value = (string) ($this->$field ?? '');
        $this->$field = (string) $callable($value); // cast نهایی
    }

    public function normalizeAll(): void
    {
        foreach (array_keys($this->normalizers) as $field) {
            $this->normalizeField($field);
        }
    }

    protected function rules(): array
    {
        return [
            'first_name_fa' => ['bail', 'required', 'string', 'min:2', 'max:40', new PersianName],
            'last_name_fa' => ['bail', 'required', 'string', 'min:2', 'max:50', new PersianName],
            'nationality_type' => ['required', Rule::enum(NationalityType::class)],
            'identity' => ['bail', 'required', 'string',
                Rule::when(
                    $this->nationality_type === NationalityType::Iranian,
                    ['digits:10', new NationalCode],
                    ['digits_between:6,20', new NotIranianNationalCode],
                ),
                'unique:people,identity',
            ],
            'mobile' => ['bail', 'required', 'digits:11', 'starts_with:09'],
        ];
    }
    public function continueRegister(): void
    {
        $this->normalizeAll();
        $this->validate();

    }
};
