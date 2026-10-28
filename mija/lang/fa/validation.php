<?php

return [
    'required' => 'تکمیل «:attribute» الزامی است.',
    'string' => ':attribute باید متن باشد.',
    'integer' => ':attribute باید عدد صحیح باشد.',
    'numeric' => ':attribute باید عدد باشد.',
    'min' => ['string' => ':attribute باید حداقل :min کاراکتر باشد.', 'numeric' => ':attribute باید حداقل :min باشد.', 'array' => 'حداقل :min مورد انتخاب کنید.', 'file' => ':attribute باید حداقل :min کیلوبایت باشد.'],
    'max' => ['string' => ':attribute باید حداکثر :max کاراکتر باشد.', 'numeric' => ':attribute باید حداکثر :max باشد.', 'array' => 'حداکثر :max مورد مجاز است.', 'file' => ':attribute باید حداکثر :max کیلوبایت باشد.'],
    'between' => ['numeric' => ':attribute باید بین :min و :max باشد.'],
    'unique' => ':attribute قبلاً ثبت شده است.',
    'confirmed' => 'تکرار :attribute مطابقت ندارد.',
    'email' => 'ایمیل معتبر نیست.',
    'regex' => 'قالب :attribute معتبر نیست.',
    'in' => ':attribute انتخاب‌شده معتبر نیست.',
    'image' => ':attribute باید تصویر باشد.',
    'mimes' => ':attribute باید یکی از فرمت‌های :values باشد.',
    'date' => ':attribute تاریخ معتبر نیست.',
    'after' => ':attribute باید بعد از :date باشد.',
    'exists' => ':attribute معتبر نیست.',
    'current_password' => 'رمز عبور فعلی نادرست است.',
    'attributes' => [
        'name' => 'نام', 'phone' => 'شماره موبایل', 'password' => 'رمز عبور', 'email' => 'ایمیل',
        'title' => 'عنوان', 'type' => 'نوع', 'province' => 'استان', 'city' => 'شهر', 'description' => 'توضیحات',
        'price_weekday' => 'قیمت میان‌هفته', 'price_weekend' => 'قیمت آخر هفته', 'capacity_base' => 'ظرفیت استاندارد',
        'capacity_max' => 'حداکثر ظرفیت', 'sheba' => 'شماره شبا', 'amount' => 'مبلغ', 'code' => 'کد', 'comment' => 'متن نظر',
        'check_in' => 'تاریخ ورود', 'check_out' => 'تاریخ خروج', 'body' => 'پیام', 'value' => 'مقدار',
    ],
];
