<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Carbon-ESG zh_TW translations for Laravel's validation rules. Keep
    | the voice consistent with the frontend Field hint copy.
    | Attribute names map at the bottom so the substituted :attribute reads
    | naturally in Chinese (e.g. "電子郵件" not "email").
    |
    */

    'accepted' => ':attribute 必須接受。',
    'accepted_if' => '當 :other 為 :value 時,:attribute 必須接受。',
    'active_url' => ':attribute 不是有效的網址。',
    'after' => ':attribute 必須晚於 :date。',
    'after_or_equal' => ':attribute 必須晚於或等於 :date。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、數字、底線、連字號。',
    'alpha_num' => ':attribute 只能包含字母與數字。',
    'array' => ':attribute 必須是陣列。',
    'ascii' => ':attribute 只能包含單位元組的字母、數字、符號。',
    'before' => ':attribute 必須早於 :date。',
    'before_or_equal' => ':attribute 必須早於或等於 :date。',
    'between' => [
        'array' => ':attribute 必須有 :min 至 :max 個項目。',
        'file' => ':attribute 必須介於 :min 至 :max KB 之間。',
        'numeric' => ':attribute 必須介於 :min 至 :max 之間。',
        'string' => ':attribute 必須介於 :min 至 :max 個字元之間。',
    ],
    'boolean' => ':attribute 必須是 true 或 false。',
    'can' => ':attribute 包含未授權的值。',
    'confirmed' => ':attribute 與確認欄位不一致。',
    'contains' => ':attribute 缺少必要的值。',
    'current_password' => '密碼不正確。',
    'date' => ':attribute 不是有效的日期格式。',
    'date_equals' => ':attribute 必須是 :date 當天。',
    'date_format' => ':attribute 必須符合 :format 格式。',
    'decimal' => ':attribute 必須包含 :decimal 位小數。',
    'declined' => ':attribute 必須拒絕。',
    'declined_if' => '當 :other 為 :value 時,:attribute 必須拒絕。',
    'different' => ':attribute 與 :other 必須不同。',
    'digits' => ':attribute 必須是 :digits 位數字。',
    'digits_between' => ':attribute 必須介於 :min 至 :max 位數字之間。',
    'dimensions' => ':attribute 圖片尺寸無效。',
    'distinct' => ':attribute 已存在重複的值。',
    'doesnt_end_with' => ':attribute 不可以這些值結尾::values。',
    'doesnt_start_with' => ':attribute 不可以這些值開頭::values。',
    'email' => ':attribute 必須是有效的電子郵件格式。',
    'ends_with' => ':attribute 必須以這些值之一結尾::values。',
    'enum' => '所選的 :attribute 無效。',
    'exists' => '所選的 :attribute 無效。',
    'extensions' => ':attribute 必須是這些副檔名之一::values。',
    'file' => ':attribute 必須是檔案。',
    'filled' => ':attribute 必須有值。',
    'gt' => [
        'array' => ':attribute 必須多於 :value 個項目。',
        'file' => ':attribute 必須大於 :value KB。',
        'numeric' => ':attribute 必須大於 :value。',
        'string' => ':attribute 必須多於 :value 個字元。',
    ],
    'gte' => [
        'array' => ':attribute 必須有 :value 個或更多項目。',
        'file' => ':attribute 必須大於或等於 :value KB。',
        'numeric' => ':attribute 必須大於或等於 :value。',
        'string' => ':attribute 必須有 :value 個或更多字元。',
    ],
    'hex_color' => ':attribute 必須是有效的十六進位顏色碼。',
    'image' => ':attribute 必須是圖片檔。',
    'in' => '所選的 :attribute 無效。',
    'in_array' => ':attribute 不在 :other 中。',
    'integer' => ':attribute 必須是整數。',
    'ip' => ':attribute 必須是有效的 IP 位址。',
    'ipv4' => ':attribute 必須是有效的 IPv4 位址。',
    'ipv6' => ':attribute 必須是有效的 IPv6 位址。',
    'json' => ':attribute 必須是有效的 JSON 字串。',
    'list' => ':attribute 必須是陣列列表。',
    'lowercase' => ':attribute 必須全部小寫。',
    'lt' => [
        'array' => ':attribute 必須少於 :value 個項目。',
        'file' => ':attribute 必須小於 :value KB。',
        'numeric' => ':attribute 必須小於 :value。',
        'string' => ':attribute 必須少於 :value 個字元。',
    ],
    'lte' => [
        'array' => ':attribute 不可超過 :value 個項目。',
        'file' => ':attribute 必須小於或等於 :value KB。',
        'numeric' => ':attribute 必須小於或等於 :value。',
        'string' => ':attribute 不可超過 :value 個字元。',
    ],
    'mac_address' => ':attribute 必須是有效的 MAC 位址。',
    'max' => [
        'array' => ':attribute 不可超過 :max 個項目。',
        'file' => ':attribute 不可大於 :max KB。',
        'numeric' => ':attribute 不可大於 :max。',
        'string' => ':attribute 不可超過 :max 個字元。',
    ],
    'max_digits' => ':attribute 不可超過 :max 位數字。',
    'mimes' => ':attribute 必須是這些檔案類型::values。',
    'mimetypes' => ':attribute 必須是這些檔案類型::values。',
    'min' => [
        'array' => ':attribute 至少需要 :min 個項目。',
        'file' => ':attribute 至少 :min KB。',
        'numeric' => ':attribute 至少 :min。',
        'string' => ':attribute 至少需要 :min 個字元。',
    ],
    'min_digits' => ':attribute 至少需要 :min 位數字。',
    'missing' => ':attribute 欄位必須不存在。',
    'missing_if' => '當 :other 為 :value 時,:attribute 欄位必須不存在。',
    'missing_unless' => '除非 :other 為 :value,否則 :attribute 欄位必須不存在。',
    'missing_with' => '當 :values 存在時,:attribute 欄位必須不存在。',
    'missing_with_all' => '當 :values 全部存在時,:attribute 欄位必須不存在。',
    'multiple_of' => ':attribute 必須是 :value 的倍數。',
    'not_in' => '所選的 :attribute 無效。',
    'not_regex' => ':attribute 格式無效。',
    'numeric' => ':attribute 必須是數字。',
    'password' => [
        'letters' => ':attribute 必須至少包含一個字母。',
        'mixed' => ':attribute 必須至少包含一個大寫與一個小寫字母。',
        'numbers' => ':attribute 必須至少包含一個數字。',
        'symbols' => ':attribute 必須至少包含一個符號。',
        'uncompromised' => '輸入的 :attribute 已出現在資料外洩名單,請改用其他密碼。',
    ],
    'present' => ':attribute 欄位必須存在。',
    'present_if' => '當 :other 為 :value 時,:attribute 欄位必須存在。',
    'present_unless' => '除非 :other 為 :value,否則 :attribute 欄位必須存在。',
    'present_with' => '當 :values 存在時,:attribute 欄位必須存在。',
    'present_with_all' => '當 :values 全部存在時,:attribute 欄位必須存在。',
    'prohibited' => ':attribute 欄位被禁止。',
    'prohibited_if' => '當 :other 為 :value 時,:attribute 欄位被禁止。',
    'prohibited_if_accepted' => '當 :other 已接受時,:attribute 欄位被禁止。',
    'prohibited_if_declined' => '當 :other 已拒絕時,:attribute 欄位被禁止。',
    'prohibited_unless' => '除非 :other 在 :values 中,否則 :attribute 欄位被禁止。',
    'prohibits' => ':attribute 欄位禁止 :other 出現。',
    'regex' => ':attribute 格式無效。',
    'required' => ':attribute 為必填欄位。',
    'required_array_keys' => ':attribute 欄位必須包含::values。',
    'required_if' => '當 :other 為 :value 時,:attribute 為必填欄位。',
    'required_if_accepted' => '當 :other 已接受時,:attribute 為必填欄位。',
    'required_if_declined' => '當 :other 已拒絕時,:attribute 為必填欄位。',
    'required_unless' => '除非 :other 在 :values 中,否則 :attribute 為必填欄位。',
    'required_with' => '當 :values 存在時,:attribute 為必填欄位。',
    'required_with_all' => '當 :values 全部存在時,:attribute 為必填欄位。',
    'required_without' => '當 :values 不存在時,:attribute 為必填欄位。',
    'required_without_all' => '當 :values 全部不存在時,:attribute 為必填欄位。',
    'same' => ':attribute 必須與 :other 相同。',
    'size' => [
        'array' => ':attribute 必須包含 :size 個項目。',
        'file' => ':attribute 必須是 :size KB。',
        'numeric' => ':attribute 必須是 :size。',
        'string' => ':attribute 必須是 :size 個字元。',
    ],
    'starts_with' => ':attribute 必須以這些值之一開頭::values。',
    'string' => ':attribute 必須是字串。',
    'timezone' => ':attribute 必須是有效的時區。',
    'unique' => ':attribute 已被使用。',
    'uploaded' => ':attribute 上傳失敗。',
    'uppercase' => ':attribute 必須全部大寫。',
    'url' => ':attribute 格式無效。',
    'ulid' => ':attribute 必須是有效的 ULID。',
    'uuid' => ':attribute 必須是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Replaces the :attribute placeholder with a Chinese label. Keep these
    | aligned with the Field labels in frontend/app/(auth)/.
    |
    */

    'attributes' => [
        'name' => '姓名',
        'email' => '電子郵件',
        'password' => '密碼',
        'password_confirmation' => '確認密碼',
    ],

];
