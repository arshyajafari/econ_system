<?php

namespace App\Enums;

enum DoctorSpecialty: string
{
    case COSMETIC_DERMATOLOGY = 'متخصص پوست، مو و زیبایی (درماتولوژی)';
    case PEDIATRICS = 'متخصص اطفال (پدیاتریک)';
    case GYNECOLOGY_AND_OBSTETRICS = 'متخصص زنان و زایمان';
    case ALLERGY_AND_IMMUNOLOGY = 'متخصص ایمونولوژی و آلرژی';
    case ENDOCRINOLOGY = 'متخصص غدد (اندوکرینولوژی)';
    case NUTRITIONIST = 'متخصص تغذیه';
    case GENERAL_PRACTITIONER = 'پزشک عمومی';
}
