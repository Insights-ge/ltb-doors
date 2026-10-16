<?php

return [
    'layout' => [
        'components' => 'კომპონენტები',
        'create_component' => 'კომპონენტის შექმნა',
        'edit_component' => 'კომპონენტის რედაქტირება',
    ],
    'table' => [
        'type' => 'ტიპი',
        'code' => 'კოდი',
        'description' => 'აღწერა',
        'color' => 'ფერი',
        'unique_code' => 'არტიკული',
        'empty_heading' => 'კომპონენტები ვერ მოიძებნა',
        'empty_description' => 'კომპონენტები იტვირთება პროდუქტის იმპორტიდან.',
        'delete_in_use_title' => 'კომპონენტი გამოიყენება',
        'delete_in_use_body' => 'ეს კომპონენტი გამოიყენება ერთ ან მეტ კარის ვარიანტში და მისი წაშლა შეუძლებელია.',
    ],
    'form' => [
        'type' => 'ტიპი',
        'code' => 'კოდი',
        'description' => 'აღწერა',
        'color' => 'ფერი',
        'unique_code' => 'არტიკული',
    ],
    'import' => [
        'actions' => [
            'download_sample' => 'ნიმუშის ჩამოტვირთვა',
            'sample_file_name' => 'components-import-sample.xlsx',
            'import' => 'Excel-იდან იმპორტი',
            'modal_heading' => 'კომპონენტების იმპორტი Excel-იდან',
            'modal_description' => 'სვეტები: type, unique_code, code, description, color. ზუსტი სათაურებისთვის ჩამოტვირთეთ ნიმუში. არსებული კომპონენტები განახლდება ფაილიდან; ერთი შეცდომაც კი აჩერებს მთელ იმპორტს.',
            'modal_submit' => 'იმპორტი',
            'file' => 'Excel ფაილი (.xlsx)',
            'preview' => 'წინასწარი ნახვა',
            'preview_counts' => 'შეიქმნება: :create · განახლდება: :update · უცვლელი: :unchanged',
            'preview_errors' => 'ნაპოვნი შეცდომები: :count. გაასწორეთ ფაილი და ატვირთეთ თავიდან.',
            'file_has_errors' => 'ფაილში შეცდომებია. გაასწორეთ და ატვირთეთ თავიდან.',
            'queued_title' => 'იმპორტი დაიწყო',
            'queued_body' => 'დასრულებისას მიიღებთ შეტყობინებას.',
        ],
        'errors' => [
            'required' => ':column სავალდებულოა.',
            'too_long' => ':column არ უნდა აღემატებოდეს :max სიმბოლოს.',
            'invalid_type' => 'ტიპი ":value" არასწორია. დასაშვებია: :allowed.',
            'invalid_color' => 'ფერი ":value" არასწორია. დასაშვებია: :allowed.',
            'duplicate_in_file' => 'კომპონენტი :unique_code (:type) ასევე არის მწკრივში :row.',
        ],
        'notifications' => [
            'succeeded_title' => 'კომპონენტების იმპორტი დასრულდა',
            'succeeded_body' => 'შეიქმნა: :created, განახლდა: :updated, უცვლელი: :unchanged.',
            'failed_title' => 'კომპონენტების იმპორტი ვერ შესრულდა',
            'failed_body' => 'არაფერი შენახულა. გაასწორეთ ფაილი და სცადეთ თავიდან. ნაპოვნი შეცდომები: :count.',
            'crashed_body' => 'იმპორტისას რაღაც შეცდომა მოხდა. არაფერი შენახულა. გთხოვთ, სცადოთ თავიდან.',
        ],
    ],
];
