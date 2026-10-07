<?php

namespace App\Enums;

enum DoctorSpecialty: string
{
    case COSMETIC_DERMATOLOGY = 'متخصص پوست، مو و زیبایی (درماتولوژی)';
    case PEDIATRICS = 'متخصص اطفال (پدیاتریک)';
    case OBSTETRICS_GYNECOLOGY = 'متخصص زنان و زایمان';
    case ALLERGY_IMMUNOLOGY = 'متخصص ایمونولوژی و آلرژی';
    case ENDOCRINOLOGY = 'متخصص غدد (اندوکرینولوژی)';
    case NUTRITIONIST = 'متخصص تغذیه';
}
