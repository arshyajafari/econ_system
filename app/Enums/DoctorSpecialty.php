<?php

    namespace App\Enums;

    enum DoctorSpecialty: string {
        case COSMETIC_DERMATOLOGY = 'متخصص پوست، مو و زیبایی';
        case GENERAL_PRACTITIONER = 'پزشک عمومی';
        case PEDIATRIC_DERMATOLOGY = 'متخصص پوست اطفال و کودکان';
        case ALLERGY_AND_IMMUNOLOGY = 'متخصص آلرژی و ایمنی‌شناسی';
        case PHLEBOLOGY = 'متخصص ورید و عروق';
        case ENDOCRINOLOGY = 'متخصص غدد و متابولیسم';
        case NUTRITIONIST = 'متخصص تغذیه';
    }
