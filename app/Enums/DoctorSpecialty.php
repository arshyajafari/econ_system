<?php

namespace App\Enums;

enum DoctorSpecialty: string
{
    case COSMETIC_DERMATOLOGY = 'متخصص پوست، مو و زیبایی';
    case GENERAL_PRACTITIONER = 'پزشک عمومی';
    case PEDIATRICS = 'متخصص اطفال (پدیاتریک)';
    case WOMEN_AND_OBSTETRICS = 'متخصص زنان و زایمان';
    case IMMUNOLOGY_AND_ALLERGY = 'متخصص ایمونولوژی و آلرژی';
    case ENDOCRINOLOGY = 'متخصص غدد (اندوکرینولوژی)';
    case NUTRITIONIST = 'متخصص تغذیه';

    /**
     * Legacy value kept so existing historical records remain readable.
     * It is intentionally not exposed as a selectable specialty in the UI.
     */
    case LEGACY_PHLEBOLOGY = 'متخصص ورید و عروق';
}
