<?php

/**
 * @file plugins/generic/thoth/classes/i18n/ThothLocaleCode.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth Open Metadata
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocaleCode
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class to normalize PKP locales to Thoth LocaleCode values
 */

class ThothLocaleCode
{
    private const SUPPORTED_LOCALE_CODES = [
        'EN' => true, 'AF' => true, 'AF_NA' => true, 'AF_ZA' => true, 'AGQ' => true, 'AGQ_CM' => true, 'AK' => true, 'AK_GH' => true, 'SQ' => true, 'SQ_AL' => true, 'AM' => true, 'AM_ET' => true,
        'AIG' => true, 'AR' => true, 'AR_DZ' => true, 'AR_BH' => true, 'AR_EG' => true, 'AR_IQ' => true, 'AR_JO' => true, 'AR_KW' => true, 'AR_LB' => true, 'AR_LY' => true, 'AR_MA' => true, 'AR_OM' => true,
        'AR_QA' => true, 'AR_SA' => true, 'AR_SD' => true, 'AR_SY' => true, 'AR_TN' => true, 'AR_AE' => true, 'AR001' => true, 'AR_YE' => true, 'HY' => true, 'HY_AM' => true, 'AS' => true, 'AS_IN' => true,
        'AST' => true, 'AST_ES' => true, 'ASA' => true, 'ASA_TZ' => true, 'AZ' => true, 'AZ_CYRL' => true, 'AZ_CYRL_AZ' => true, 'AZ_LATN' => true, 'AZ_LATN_AZ' => true, 'KSF' => true, 'KSF_CM' => true, 'BAH' => true,
        'BM' => true, 'BM_ML' => true, 'BAS' => true, 'BAS_CM' => true, 'EU' => true, 'EU_ES' => true, 'BE' => true, 'BE_BY' => true, 'BEM' => true, 'BEM_ZM' => true, 'BEZ' => true, 'BEZ_TZ' => true,
        'BN' => true, 'BN_BD' => true, 'BN_IN' => true, 'BRX' => true, 'BRX_IN' => true, 'BS' => true, 'BS_BA' => true, 'BR' => true, 'BR_FR' => true, 'BG' => true, 'BG_BG' => true, 'MY' => true,
        'MY_MM' => true, 'CA' => true, 'CA_ES' => true, 'CKB' => true, 'KMR' => true, 'SDH' => true, 'TZM' => true, 'TZM_LATN' => true, 'TZM_LATN_MA' => true, 'CHR' => true, 'CHR_US' => true, 'CGG' => true,
        'CGG_UG' => true, 'ZH' => true, 'ZH_HANS' => true, 'ZH_CN' => true, 'ZH_HANS_CN' => true, 'ZH_HANS_HK' => true, 'ZH_HANS_MO' => true, 'ZH_HANS_SG' => true, 'ZH_HANT' => true, 'ZH_HANT_HK' => true, 'ZH_HANT_MO' => true, 'ZH_HANT_TW' => true,
        'SWC' => true, 'SWC_CD' => true, 'KW' => true, 'KW_GB' => true, 'HR' => true, 'HR_HR' => true, 'CS' => true, 'CS_CZ' => true, 'DA' => true, 'DA_DK' => true, 'DUA' => true, 'DUA_CM' => true,
        'DV' => true, 'NL' => true, 'NL_AW' => true, 'NL_BE' => true, 'NL_CW' => true, 'NL_NL' => true, 'NL_SX' => true, 'EBU' => true, 'EBU_KE' => true, 'EN_AI' => true, 'EN_AS' => true, 'EN_AU' => true,
        'EN_AT' => true, 'EN_BB' => true, 'EN_BE' => true, 'EN_BZ' => true, 'EN_BM' => true, 'EN_BW' => true, 'EN_IO' => true, 'EN_BI' => true, 'EN_CM' => true, 'EN_CA' => true, 'EN_KY' => true, 'EN_CX' => true,
        'EN_CC' => true, 'EN_CK' => true, 'EN_CY' => true, 'EN_DK' => true, 'EN_DG' => true, 'EN_DM' => true, 'EN_EG' => true, 'EN_ER' => true, 'EN_EU' => true, 'EN_FK' => true, 'EN_FJ' => true, 'EN_FI' => true,
        'EN_GM' => true, 'EN_DE' => true, 'EN_GH' => true, 'EN_GI' => true, 'EN_GD' => true, 'EN_GU' => true, 'EN_GG' => true, 'EN_GY' => true, 'EN_HK' => true, 'EN_IN' => true, 'EN_IE' => true, 'EN_IM' => true,
        'EN_IL' => true, 'EN_JM' => true, 'EN_JE' => true, 'EN_KE' => true, 'EN_KI' => true, 'EN_KW' => true, 'EN_LS' => true, 'EN_MO' => true, 'EN_MG' => true, 'EN_MW' => true, 'EN_MY' => true, 'EN_MT' => true,
        'EN_MH' => true, 'EN_MU' => true, 'EN_FM' => true, 'EN_MS' => true, 'EN_NA' => true, 'EN_NR' => true, 'EN_NL' => true, 'EN_NZ' => true, 'EN_NG' => true, 'EN_NU' => true, 'EN_NF' => true, 'EN_MP' => true,
        'EN_NO' => true, 'EN_PA' => true, 'EN_PK' => true, 'EN_PW' => true, 'EN_PG' => true, 'EN_PH' => true, 'EN_PN' => true, 'EN_PR' => true, 'EN_RW' => true, 'EN_WS' => true, 'EN_SA' => true, 'EN_SC' => true,
        'EN_SL' => true, 'EN_SG' => true, 'EN_SX' => true, 'EN_SI' => true, 'EN_SB' => true, 'EN_SS' => true, 'EN_SH' => true, 'EN_KN' => true, 'EN_LC' => true, 'SVC' => true, 'VIC' => true, 'EN_SD' => true,
        'EN_SZ' => true, 'EN_SE' => true, 'EN_CH' => true, 'EN_TZ' => true, 'EN_TK' => true, 'EN_TO' => true, 'EN_TT' => true, 'EN_TV' => true, 'EN_ZA' => true, 'EN_AE' => true, 'EN_UM' => true, 'EN_VI' => true,
        'EN_US_POSIX' => true, 'EN_UG' => true, 'EN_GB' => true, 'EN_US' => true, 'EN_VU' => true, 'EN_ZM' => true, 'EN_ZW' => true, 'EO' => true, 'ET' => true, 'ET_EE' => true, 'EE' => true, 'EE_GH' => true,
        'EE_TG' => true, 'EWO' => true, 'EWO_CM' => true, 'FO' => true, 'FO_FO' => true, 'FIL' => true, 'FIL_PH' => true, 'FI' => true, 'FI_FI' => true, 'FR' => true, 'FR_BE' => true, 'FR_BJ' => true,
        'FR_BF' => true, 'FR_BI' => true, 'FR_CM' => true, 'FR_CA' => true, 'FR_CF' => true, 'FR_TD' => true, 'FR_KM' => true, 'FR_CG' => true, 'FR_CD' => true, 'FR_CI' => true, 'FR_DJ' => true, 'FR_GQ' => true,
        'FR_FR' => true, 'FR_GF' => true, 'FR_GA' => true, 'FR_GP' => true, 'FR_GN' => true, 'FR_LU' => true, 'FR_MG' => true, 'FR_ML' => true, 'FR_MQ' => true, 'FR_YT' => true, 'FR_MC' => true, 'FR_NE' => true,
        'FR_RW' => true, 'FR_RE' => true, 'FR_BL' => true, 'FR_MF' => true, 'FR_MU' => true, 'FR_SN' => true, 'FR_CH' => true, 'FR_TG' => true, 'FF' => true, 'FF_SN' => true, 'GL' => true, 'GL_ES' => true,
        'LAO' => true, 'LG' => true, 'LG_UG' => true, 'KA' => true, 'KA_GE' => true, 'DE' => true, 'DE_AT' => true, 'DE_BE' => true, 'DE_DE' => true, 'DE_LI' => true, 'DE_LU' => true, 'DE_CH' => true,
        'EL' => true, 'EL_CY' => true, 'EL_GR' => true, 'GU' => true, 'GU_IN' => true, 'GUZ' => true, 'GUZ_KE' => true, 'HA' => true, 'HA_LATN' => true, 'HA_LATN_GH' => true, 'HA_LATN_NE' => true, 'HA_LATN_NG' => true,
        'HAW' => true, 'HAW_US' => true, 'HE' => true, 'HE_IL' => true, 'HI' => true, 'HI_IN' => true, 'HU' => true, 'HU_HU' => true, 'IS' => true, 'IS_IS' => true, 'IG' => true, 'IG_NG' => true,
        'SMN' => true, 'SMN_FI' => true, 'ID' => true, 'ID_ID' => true, 'GA' => true, 'GA_IE' => true, 'IT' => true, 'IT_IT' => true, 'IT_CH' => true, 'JA' => true, 'JA_JP' => true, 'DYO' => true,
        'DYO_SN' => true, 'KEA' => true, 'KEA_CV' => true, 'KAB' => true, 'KAB_DZ' => true, 'KL' => true, 'KL_GL' => true, 'KLN' => true, 'KLN_KE' => true, 'KAM' => true, 'KAM_KE' => true, 'KN' => true,
        'KN_IN' => true, 'KAA' => true, 'KK' => true, 'KK_CYRL' => true, 'KK_CYRL_KZ' => true, 'KM' => true, 'KM_KH' => true, 'KI' => true, 'KI_KE' => true, 'RW' => true, 'RW_RW' => true, 'KOK' => true,
        'KOK_IN' => true, 'KO' => true, 'KO_KR' => true, 'KHQ' => true, 'KHQ_ML' => true, 'SES' => true, 'SES_ML' => true, 'NMG' => true, 'NMG_CM' => true, 'KY' => true, 'LAG' => true, 'LAG_TZ' => true,
        'LV' => true, 'LV_LV' => true, 'LIR' => true, 'LN' => true, 'LN_CG' => true, 'LN_CD' => true, 'LT' => true, 'LT_LT' => true, 'LU' => true, 'LU_CD' => true, 'LUO' => true, 'LUO_KE' => true,
        'LUY' => true, 'LUY_KE' => true, 'MK' => true, 'MK_MK' => true, 'JMC' => true, 'JMC_TZ' => true, 'MGH' => true, 'MGH_MZ' => true, 'KDE' => true, 'KDE_TZ' => true, 'MG' => true, 'MG_MG' => true,
        'MS' => true, 'MS_BN' => true, 'MS_MY' => true, 'ML' => true, 'ML_IN' => true, 'MT' => true, 'MT_MT' => true, 'GV' => true, 'GV_GB' => true, 'MR' => true, 'MR_IN' => true, 'MAS' => true,
        'MAS_KE' => true, 'MAS_TZ' => true, 'MER' => true, 'MER_KE' => true, 'MN' => true, 'MFE' => true, 'MFE_MU' => true, 'MUA' => true, 'MUA_CM' => true, 'NAQ' => true, 'NAQ_NA' => true, 'NE' => true,
        'NE_IN' => true, 'NE_NP' => true, 'SE' => true, 'SE_FI' => true, 'SE_NO' => true, 'SE_SE' => true, 'ND' => true, 'ND_ZW' => true, 'NB' => true, 'NB_NO' => true, 'NN' => true, 'NN_NO' => true,
        'NUS' => true, 'NUS_SD' => true, 'NYN' => true, 'NYN_UG' => true, 'OR' => true, 'OR_IN' => true, 'OM' => true, 'OM_ET' => true, 'OM_KE' => true, 'PS' => true, 'PS_AF' => true, 'FA' => true,
        'FA_AF' => true, 'FA_IR' => true, 'PL' => true, 'PL_PL' => true, 'PT' => true, 'PT_AO' => true, 'PT_BR' => true, 'PT_GW' => true, 'PT_MZ' => true, 'PT_PT' => true, 'PT_ST' => true, 'PA' => true,
        'PA_ARAB' => true, 'PA_ARAB_PK' => true, 'PA_GURU' => true, 'PA_GURU_IN' => true, 'RO' => true, 'RO_MD' => true, 'RO_RO' => true, 'RM' => true, 'RM_CH' => true, 'ROF' => true, 'ROF_TZ' => true, 'RN' => true,
        'RN_BI' => true, 'RU' => true, 'RU_MD' => true, 'RU_RU' => true, 'RU_UA' => true, 'RWK' => true, 'RWK_TZ' => true, 'SAQ' => true, 'SAQ_KE' => true, 'SG' => true, 'SG_CF' => true, 'SBP' => true,
        'SBP_TZ' => true, 'SA' => true, 'GD' => true, 'GD_GB' => true, 'SEH' => true, 'SEH_MZ' => true, 'SR' => true, 'SR_CYRL' => true, 'SR_CYRL_BA' => true, 'SR_CYRL_ME' => true, 'SR_CYRL_RS' => true, 'SR_LATN' => true,
        'SR_LATN_BA' => true, 'SR_LATN_ME' => true, 'SR_LATN_RS' => true, 'KSB' => true, 'KSB_TZ' => true, 'SN' => true, 'SN_ZW' => true, 'II' => true, 'II_CN' => true, 'SI' => true, 'SI_LK' => true, 'SK' => true,
        'SK_SK' => true, 'SL' => true, 'SL_SI' => true, 'XOG' => true, 'XOG_UG' => true, 'SO' => true, 'SO_DJ' => true, 'SO_ET' => true, 'SO_KE' => true, 'SO_SO' => true, 'ES' => true, 'ES_AR' => true,
        'ES_BO' => true, 'ES_CL' => true, 'ES_CO' => true, 'ES_CR' => true, 'ES_DO' => true, 'ES_EC' => true, 'ES_SV' => true, 'ES_GQ' => true, 'ES_GT' => true, 'ES_HN' => true, 'ES419' => true, 'ES_MX' => true,
        'ES_NI' => true, 'ES_PA' => true, 'ES_PY' => true, 'ES_PE' => true, 'ES_PR' => true, 'ES_ES' => true, 'ES_US' => true, 'ES_UY' => true, 'ES_VE' => true, 'SW' => true, 'SW_KE' => true, 'SW_TZ' => true,
        'SV' => true, 'SV_FI' => true, 'SV_SE' => true, 'GSW' => true, 'GSW_CH' => true, 'SHI' => true, 'SHI_LATN' => true, 'SHI_LATN_MA' => true, 'SHI_TFNG' => true, 'SHI_TFNG_MA' => true, 'DAV' => true, 'DAV_KE' => true,
        'TG' => true, 'TA' => true, 'TA_IN' => true, 'TA_LK' => true, 'TWQ' => true, 'TWQ_NE' => true, 'MI' => true, 'TE' => true, 'TE_IN' => true, 'TEO' => true, 'TEO_KE' => true, 'TEO_UG' => true,
        'TH' => true, 'TH_TH' => true, 'BO' => true, 'BO_CN' => true, 'BO_IN' => true, 'TI' => true, 'TI_ER' => true, 'TI_ET' => true, 'TO' => true, 'TO_TO' => true, 'TR' => true, 'TK' => true,
        'TR_TR' => true, 'TCH' => true, 'UK' => true, 'UK_UA' => true, 'UR' => true, 'UR_IN' => true, 'UR_PK' => true, 'UG' => true, 'UG_CN' => true, 'UZ' => true, 'UZ_ARAB' => true, 'UZ_ARAB_AF' => true,
        'UZ_CYRL' => true, 'UZ_CYRL_UZ' => true, 'UZ_LATN' => true, 'UZ_LATN_UZ' => true, 'VAI' => true, 'VAI_LATN' => true, 'VAI_LATN_LR' => true, 'VAI_VAII' => true, 'VAI_VAII_LR' => true, 'VAL' => true, 'VAL_ES' => true, 'CA_ES_VALENCIA' => true,
        'VI' => true, 'VI_VN' => true, 'VUN' => true, 'VUN_TZ' => true, 'CY' => true, 'CY_GB' => true, 'WO' => true, 'XH' => true, 'YAV' => true, 'YAV_CM' => true, 'YO' => true, 'YO_NG' => true,
        'DJE' => true, 'DJE_NE' => true, 'ZU' => true, 'ZU_ZA' => true,
    ];

    public static function fromPkpLocale(?string $locale): ?string
    {
        if (!$locale || strtolower($locale) === 'und') {
            return null;
        }

        $localeCode = strtoupper(str_replace(['-', '@'], '_', $locale));

        return isset(self::SUPPORTED_LOCALE_CODES[$localeCode]) ? $localeCode : null;
    }
}
