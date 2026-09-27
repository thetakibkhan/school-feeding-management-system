<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnwaraSchoolSeeder extends Seeder
{
    /**
     * Source order: supplied Anwara School List. EMIS values are verified from Form 7.
     *
     * @var list<array{school_code: string, emis_code: string, name: string, student_count: int, principal_name?: string, principal_mobile?: string}>
     */
    private const SCHOOLS = [
        ['school_code' => 'AN-001', 'emis_code' => '91411060101', 'name' => 'বৈরাগ সপ্রাবি', 'student_count' => 191, 'principal_name' => 'মোঃ গোলাম জিলানী'],
        ['school_code' => 'AN-002', 'emis_code' => '91411060102', 'name' => '| বদলপুরা সপ্রাবি', 'student_count' => 325],
        ['school_code' => 'AN-003', 'emis_code' => '91411060103', 'name' => 'মেরিন একাডেমী সপ্রাবি', 'student_count' => 301],
        ['school_code' => 'AN-004', 'emis_code' => '91411060104', 'name' => '| উত্তর বন্দর সপ্রাবি', 'student_count' => 274],
        ['school_code' => 'AN-005', 'emis_code' => '91411060105', 'name' => '| মধ্য বন্দর সপ্রাবি', 'student_count' => 457],
        ['school_code' => 'AN-006', 'emis_code' => '91411060106', 'name' => 'দক্ষিণ বন্দর সপ্রাবি', 'student_count' => 338],
        ['school_code' => 'AN-007', 'emis_code' => '91411060107', 'name' => 'পূর্ব বৈরাগ সপ্রাবি', 'student_count' => 634],
        ['school_code' => 'AN-008', 'emis_code' => '91411060108', 'name' => '| মহাদেবপুর সপ্রাবি', 'student_count' => 354],
        ['school_code' => 'AN-009', 'emis_code' => '91411060109', 'name' => '| উত্তর গুয়াপঞ্চক সপ্রাবি', 'student_count' => 254],
        ['school_code' => 'AN-010', 'emis_code' => '91411060110', 'name' => 'গুয়াপঞ্চক সপ্রাবি', 'student_count' => 523],
        ['school_code' => 'AN-011', 'emis_code' => '91411060201', 'name' => '| বারশত সপ্রাবি', 'student_count' => 281],
        ['school_code' => 'AN-012', 'emis_code' => '91411060202', 'name' => 'পূর্ব বোয়ালিয়া সপ্রাবি', 'student_count' => 112],
        ['school_code' => 'AN-013', 'emis_code' => '91411060203', 'name' => '| দুধকুমড়া সপ্রাবি', 'student_count' => 337],
        ['school_code' => 'AN-014', 'emis_code' => '91411060204', 'name' => 'গোবাদিয়া সপ্রাবি', 'student_count' => 218],
        ['school_code' => 'AN-015', 'emis_code' => '91411060205', 'name' => 'গুণীপ সপ্রাবি', 'student_count' => 159],
        ['school_code' => 'AN-016', 'emis_code' => '91411060301', 'name' => 'রায়পুর সপ্রাবি', 'student_count' => 262],
        ['school_code' => 'AN-017', 'emis_code' => '91411060302', 'name' => 'উত্তর পরুয়া পাড়া সপ্রাবি', 'student_count' => 194],
        ['school_code' => 'AN-018', 'emis_code' => '91411060303', 'name' => '| দক্ষিণ পরুয়াপাড়া সপ্রাবি', 'student_count' => 276],
        ['school_code' => 'AN-019', 'emis_code' => '91411060304', 'name' => '| খোর্দ গহিরা সপ্রাবি', 'student_count' => 94],
        ['school_code' => 'AN-020', 'emis_code' => '91411060305', 'name' => '| উত্তর গহিরা সপ্রাবি', 'student_count' => 257],
        ['school_code' => 'AN-021', 'emis_code' => '91411060306', 'name' => '| মধ্য গহিরা সপ্রাৰি', 'student_count' => 141],
        ['school_code' => 'AN-022', 'emis_code' => '91411060307', 'name' => '] দক্ষিণ গহিরা সপ্রাবি', 'student_count' => 981],
        ['school_code' => 'AN-023', 'emis_code' => '91411060308', 'name' => '| সরেঙ্গা সপ্রাবি', 'student_count' => 138],
        ['school_code' => 'AN-024', 'emis_code' => '91411060401', 'name' => '| বটতলী এস এম আউলিয়া সপ্রাবি', 'student_count' => 489],
        ['school_code' => 'AN-025', 'emis_code' => '91411060402', 'name' => '| চীপাতলী সপ্রাবি', 'student_count' => 189],
        ['school_code' => 'AN-026', 'emis_code' => '91411060403', 'name' => '| ছিরাবটতলী সপ্রাৰি', 'student_count' => 166],
        ['school_code' => 'AN-027', 'emis_code' => '91411060404', 'name' => '| তুলাতলী আইরমঞঙ্গল সপ্রাবি', 'student_count' => 115],
        ['school_code' => 'AN-028', 'emis_code' => '91411060405', 'name' => '| পশ্চিম বরৈয়া সপ্রাবি', 'student_count' => 126],
        ['school_code' => 'AN-029', 'emis_code' => '91411060406', 'name' => '| পূর্ব বরৈয়া সরকারি প্রাথমিক বিদ্যালয়', 'student_count' => 251],
        ['school_code' => 'AN-030', 'emis_code' => '91411060407', 'name' => '|] আইর মঞ্জল সপ্রাবি', 'student_count' => 999],
        ['school_code' => 'AN-031', 'emis_code' => '91411060408', 'name' => '| পূর্ব বটতলী সপ্রাবি', 'student_count' => 411],
        ['school_code' => 'AN-032', 'emis_code' => '91411060501', 'name' => '| বরুমচড়া সপ্রাবি', 'student_count' => 225],
        ['school_code' => 'AN-033', 'emis_code' => '91411060502', 'name' => '| বরুমচড়া ছমদিয়া সপ্রাবি', 'student_count' => 311],
        ['school_code' => 'AN-034', 'emis_code' => '91411060503', 'name' => '| উত্তর বরুমচড়া সপ্রাবি', 'student_count' => 95],
        ['school_code' => 'AN-035', 'emis_code' => '91411060504', 'name' => '| বরুমচড়া নলদিয়া সপ্রাবি', 'student_count' => 198],
        ['school_code' => 'AN-036', 'emis_code' => '91411060505', 'name' => '| পশ্চিম বরুমচড়া সপ্রাবি', 'student_count' => 371],
        ['school_code' => 'AN-037', 'emis_code' => '91411060506', 'name' => '] খুরুক্কুল সপ্রাবি', 'student_count' => 381],
        ['school_code' => 'AN-038', 'emis_code' => '91411060507', 'name' => '| উত্তর জুঁইদন্ডী সপ্রাবি', 'student_count' => 270],
        ['school_code' => 'AN-039', 'emis_code' => '91411060508', 'name' => 'দক্ষিণ জুইদন্ডী সপ্রাবি', 'student_count' => 162],
        ['school_code' => 'AN-040', 'emis_code' => '91411060509', 'name' => '| পূর্ব জুইদন্ডী সপ্রাবি', 'student_count' => 194],
        ['school_code' => 'AN-041', 'emis_code' => '91411060601', 'name' => '| তৈলারদ্বীপ সপ্রাৰি', 'student_count' => 429],
        ['school_code' => 'AN-042', 'emis_code' => '91411060602', 'name' => '| তৈলারদ্বীপ বারখাইন সপ্রাবি', 'student_count' => 83],
        ['school_code' => 'AN-043', 'emis_code' => '91411060603', 'name' => '| বারখাইন পদ্মপাড়া সপ্রাবি', 'student_count' => 228],
        ['school_code' => 'AN-044', 'emis_code' => '91411060604', 'name' => 'পশ্চিম বারখাইন সপ্রাবি', 'student_count' => 126],
        ['school_code' => 'AN-045', 'emis_code' => '91411060605', 'name' => '| বিওরী সপ্রাবি', 'student_count' => 221],
        ['school_code' => 'AN-046', 'emis_code' => '91411060606', 'name' => '| হাজীগাও বিওরী সপ্রাবি', 'student_count' => 214],
        ['school_code' => 'AN-047', 'emis_code' => '91411060607', 'name' => '| হাজীগীও সপ্রাবি', 'student_count' => 267],
        ['school_code' => 'AN-048', 'emis_code' => '91411060608', 'name' => 'শোলকাটা সপ্রাবি', 'student_count' => 204],
        ['school_code' => 'AN-049', 'emis_code' => '91411060609', 'name' => '] শিলাইগড়া সপ্রাবি', 'student_count' => 144],
        ['school_code' => 'AN-050', 'emis_code' => '91411060610', 'name' => '| সৈয়দ কুচাইয়া আমীর বক্স সপ্রাবি', 'student_count' => 197],
        ['school_code' => 'AN-051', 'emis_code' => '91411060611', 'name' => '] পূর্ব বারখাইন সপ্রাৰি', 'student_count' => 341],
        ['school_code' => 'AN-052', 'emis_code' => '91411060701', 'name' => '| আনোয়ারা মডেল সপ্রাবি', 'student_count' => 315],
        ['school_code' => 'AN-053', 'emis_code' => '91411060702', 'name' => '| আনোয়ারা সরস্বতী সপ্রাবি', 'student_count' => 197],
        ['school_code' => 'AN-054', 'emis_code' => '91411060703', 'name' => 'বিলপুর সপ্রাবি', 'student_count' => 127],
        ['school_code' => 'AN-055', 'emis_code' => '91411060704', 'name' => 'ধানপুরা বোয়ালগাঁও সপ্রাবি', 'student_count' => 999],
        ['school_code' => 'AN-056', 'emis_code' => '91411060705', 'name' => 'খিলপাড়া সপ্রাবি', 'student_count' => 495],
        ['school_code' => 'AN-057', 'emis_code' => '91411060801', 'name' => '| চাতরী সপ্রাবি', 'student_count' => 237],
        ['school_code' => 'AN-058', 'emis_code' => '91411060802', 'name' => 'বেলছুড়া সপ্রাবি', 'student_count' => 176],
        ['school_code' => 'AN-059', 'emis_code' => '91411060803', 'name' => 'কৈনপুরা সপ্রাবি', 'student_count' => 143],
        ['school_code' => 'AN-060', 'emis_code' => '91411060804', 'name' => 'সিংহারা সপ্রাবি', 'student_count' => 173],
        ['school_code' => 'AN-061', 'emis_code' => '91411060805', 'name' => 'কেঁয়াগড় সপ্রাবি', 'student_count' => 181],
        ['school_code' => 'AN-062', 'emis_code' => '91411060806', 'name' => '| ডুমুরিয়া রুদ্রা সরকারি প্রাথমিক বিদ্যালয়', 'student_count' => 63],
        ['school_code' => 'AN-063', 'emis_code' => '91411060901', 'name' => '| পরৈকোড়া সপ্রাবি', 'student_count' => 171],
        ['school_code' => 'AN-064', 'emis_code' => '91411060911', 'name' => '| কেয়াইন সপ্রাৰি', 'student_count' => 137],
        ['school_code' => 'AN-065', 'emis_code' => '91411060912', 'name' => '| পরৈকোড়া চারুশীলা সপ্রাবি', 'student_count' => 82],
        ['school_code' => 'AN-066', 'emis_code' => '91411060902', 'name' => '| তালসরা সরকারি প্রাথমিক বিদ্যালয়', 'student_count' => 131],
        ['school_code' => 'AN-067', 'emis_code' => '91411060903', 'name' => '] ওসাইন ইউছ্ুপ আলী সপ্রাবি', 'student_count' => 837],
        ['school_code' => 'AN-068', 'emis_code' => '91411060904', 'name' => '| শিলালিয়া সপ্রাবি', 'student_count' => 886],
        ['school_code' => 'AN-069', 'emis_code' => '91411060905', 'name' => '| পাটনীকোঠা সম্রাৰি', 'student_count' => 99],
        ['school_code' => 'AN-070', 'emis_code' => '91411060906', 'name' => '| মাহাতা সপ্রাৰি', 'student_count' => 729],
        ['school_code' => 'AN-071', 'emis_code' => '91411060907', 'name' => '| দেওতলা সপ্রাবি', 'student_count' => 228],
        ['school_code' => 'AN-072', 'emis_code' => '91411060908', 'name' => '| রমজান আলী সপ্রাৰি', 'student_count' => 144],
        ['school_code' => 'AN-073', 'emis_code' => '91411060909', 'name' => '| ভিংরোল সপ্রাৰি', 'student_count' => 238],
        ['school_code' => 'AN-074', 'emis_code' => '91411061001', 'name' => '| হাইলধর সপ্রাবি', 'student_count' => 121],
        ['school_code' => 'AN-075', 'emis_code' => '91411061002', 'name' => '| দক্ষিণ ইছাখালী সপ্রাৰি', 'student_count' => 209],
        ['school_code' => 'AN-076', 'emis_code' => '91411061003', 'name' => '| গীরখাইন সপ্রাৰি', 'student_count' => 132],
        ['school_code' => 'AN-077', 'emis_code' => '91411061004', 'name' => '| তেকোটা সপ্রাবি', 'student_count' => 223],
        ['school_code' => 'AN-078', 'emis_code' => '91411061005', 'name' => '| হেটিখাইন সপ্রাৰি', 'student_count' => 100],
        ['school_code' => 'AN-079', 'emis_code' => '91411061006', 'name' => '| কুনিরবিল সপ্রাবি', 'student_count' => 531],
        ['school_code' => 'AN-080', 'emis_code' => '91411061007', 'name' => '| গুজরা তিশারী সপ্রাবি', 'student_count' => 154],
        ['school_code' => 'AN-081', 'emis_code' => '91411061008', 'name' => '| গুজরা সপ্রাবি', 'student_count' => 323],
        ['school_code' => 'AN-082', 'emis_code' => '91411061009', 'name' => '| খাসখামা সপ্রাৰি', 'student_count' => 64],
        ['school_code' => 'AN-083', 'emis_code' => '91411061010', 'name' => '| উত্তর ইছাখালী সপ্রাবি', 'student_count' => 209],
        ['school_code' => 'AN-084', 'emis_code' => '91411061011', 'name' => '| মালঘর সপ্রাবি', 'student_count' => 156],
        ['school_code' => 'AN-085', 'emis_code' => '91411061012', 'name' => '| উত্তর হাইলধর সপ্রাবি', 'student_count' => 158],
        ['school_code' => 'AN-086', 'emis_code' => '91411060910', 'name' => '| পূর্বকন্যারা সপ্রাৰি', 'student_count' => 135],
        ['school_code' => 'AN-087', 'emis_code' => '91411060510', 'name' => '| বরুমচড়া আদর্শ সপ্রাবি', 'student_count' => 184],
        ['school_code' => 'AN-088', 'emis_code' => '91411060309', 'name' => '| উত্তর সরেঙা সপ্রাবি', 'student_count' => 204],
        ['school_code' => 'AN-089', 'emis_code' => '99411012001', 'name' => '| বরুমচড়া উদয়ন সপ্রাবি', 'student_count' => 198],
        ['school_code' => 'AN-090', 'emis_code' => '99411011201', 'name' => 'পূর্ব গহিরা সপ্রাবি', 'student_count' => 138],
        ['school_code' => 'AN-091', 'emis_code' => '99411011003', 'name' => 'দক্ষিণ বারশত সপ্রাবি', 'student_count' => 176],
        ['school_code' => 'AN-092', 'emis_code' => '99411012102', 'name' => '| চুন্নাপাড়া মৌলভী ফররুখ আহমদ সপ্রাবি', 'student_count' => 59],
        ['school_code' => 'AN-093', 'emis_code' => '99411012003', 'name' => '| রুদুরা সারদা চরণ সপ্রাৰি', 'student_count' => 67],
        ['school_code' => 'AN-094', 'emis_code' => '99411069001', 'name' => '] চেনামতি গিরিবালা সপ্াবি', 'student_count' => 52],
        ['school_code' => 'AN-095', 'emis_code' => '99411011801', 'name' => '| উত্তর চুন্নাপাড়া আলিমুদ্দীন দোভা সপ্রাবি', 'student_count' => 189],
        ['school_code' => 'AN-096', 'emis_code' => '99411010501', 'name' => '| হাজী মীর আহমদ সপ্রাৰি', 'student_count' => 207],
        ['school_code' => 'AN-097', 'emis_code' => '99411069002', 'name' => '| মধ্য জুইদন্তী সপ্রাবি', 'student_count' => 165],
        ['school_code' => 'AN-098', 'emis_code' => '99411011901', 'name' => '| বরুমচড়া জনতা সপ্রাবি', 'student_count' => 927],
        ['school_code' => 'AN-099', 'emis_code' => '99411010802', 'name' => '| আলহাজ ফজলুল কাদের চৌধুরী', 'student_count' => 888],
        ['school_code' => 'AN-100', 'emis_code' => '99411012308', 'name' => '| গুন্ীপ আদর্শ সপ্রাবি', 'student_count' => 159],
        ['school_code' => 'AN-101', 'emis_code' => '99411012309', 'name' => '] আনোয়ারা অগ্রযাত্রা সপ্রাবি', 'student_count' => 237],
        ['school_code' => 'AN-102', 'emis_code' => '99411010801', 'name' => '| পশ্চিম রায়পুর সপ্রাবি', 'student_count' => 132],
        ['school_code' => 'AN-103', 'emis_code' => '99411011602', 'name' => '| মামুর খাইন সপ্রাবি', 'student_count' => 621],
        ['school_code' => 'AN-104', 'emis_code' => '99411011904', 'name' => '| পশ্চিমচাল সুলতানিয়া সপ্রাবি', 'student_count' => 172],
        ['school_code' => 'AN-105', 'emis_code' => '99411069004', 'name' => '] দক্ষিণ শোলকাটা সপ্রাৰি', 'student_count' => 558],
        ['school_code' => 'AN-106', 'emis_code' => '99411010109', 'name' => '| মহতরপাড়া সপ্রাবি', 'student_count' => 115],
        ['school_code' => 'AN-107', 'emis_code' => '99411010312', 'name' => '| পূর্ব সিংহারা পশ্চিম কন্যারা সপ্রাৰি', 'student_count' => 153],
        ['school_code' => 'AN-108', 'emis_code' => '99411020401', 'name' => '] দক্ষিণ তৈলারদ্বীপ সপ্রাৰি', 'student_count' => 147],
        ['school_code' => 'AN-109', 'emis_code' => '99411020701', 'name' => '| মধ্য শিলালিপাড়া সপ্রাবি', 'student_count' => 97],
        ['school_code' => 'AN-110', 'emis_code' => '99411069003', 'name' => '| মাহাতা পাটানিকোঠা সপ্রাবি', 'student_count' => 63],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::SCHOOLS as $attributes) {
                $school = School::query()->updateOrCreate(
                    ['school_code' => $attributes['school_code']],
                    array_filter([
                        'emis_code' => $attributes['emis_code'],
                        'name' => $this->verifiedName($attributes['name']),
                        'principal_name' => $attributes['principal_name'] ?? null,
                        'principal_mobile' => $attributes['principal_mobile'] ?? null,
                    ], static fn (?string $value): bool => $value !== null),
                );

                $school->studentCounts()->updateOrCreate(
                    ['effective_start_date' => '2026-09-01'],
                    ['student_count' => $attributes['student_count']],
                );
            }
        });
    }

    private function verifiedName(string $name): string
    {
        return str_replace(
            ['সপ্রাৰি', 'সপ্াবি', 'সম্রাবি', 'সপ্রাণী'],
            ['সপ্রাবি', 'সপ্রাবি', 'সপ্রাবি', 'সপ্রাবি'],
            trim($name, ' |[]'),
        );
    }
}
