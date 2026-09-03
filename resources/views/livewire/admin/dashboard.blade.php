<div class="admin-dashboard">
    <div class="dash-hero mb-4">
        <div class="dash-hero-content">
            <p class="dash-hero-kicker mb-1">Admin Panel</p>
            <h4 class="dash-hero-title mb-2">Welcome back</h4>
            <p class="dash-hero-text mb-0">
                Manage schools, students, exams, and results from one place.
            </p>
        </div>
        <div class="dash-hero-badge d-none d-md-flex">
            <i class="bx bxs-dashboard"></i>
        </div>
    </div>

    @php
        $sectionCount = $sections;
        $dashboardSections = [
            [
                'title' => 'Organization',
                'subtitle' => 'Schools, students, and structure',
                'tiles' => array_filter([
                    getPermissions('schools', 'view') ? [
                        'label' => 'Schools',
                        'value' => $schools,
                        'route' => route('admin.schools'),
                        'icon' => 'bx bxs-school',
                        'tone' => 'tone-blue',
                    ] : null,
                    getPermissions('students', 'view') ? [
                        'label' => 'Students',
                        'value' => $students,
                        'route' => route('admin.students'),
                        'icon' => 'bx bxs-user-detail',
                        'tone' => 'tone-rose',
                    ] : null,
                    getPermissions('classes', 'view') ? [
                        'label' => 'Grades',
                        'value' => $grades,
                        'route' => route('admin.grades'),
                        'icon' => 'bx bxs-layer',
                        'tone' => 'tone-green',
                    ] : null,
                    getPermissions('schools', 'view') ? [
                        'label' => 'Divisions / Sections',
                        'value' => $sectionCount,
                        'route' => route('admin.schools'),
                        'icon' => 'bx bx-git-repo-forked',
                        'tone' => 'tone-slate',
                    ] : null,
                ]),
            ],
            [
                'title' => 'Assessment',
                'subtitle' => 'Question banks, exams, and results',
                'tiles' => array_filter([
                    getPermissions('question_banks', 'view') ? [
                        'label' => 'Question Banks',
                        'value' => $banks,
                        'route' => route('admin.question-banks'),
                        'icon' => 'bx bx-question-mark',
                        'tone' => 'tone-cyan',
                    ] : null,
                    getPermissions('exams', 'view') ? [
                        'label' => 'Exams',
                        'value' => $exams,
                        'route' => route('admin.exams'),
                        'icon' => 'bx bxs-spreadsheet',
                        'tone' => 'tone-amber',
                    ] : null,
                    getPermissions('results', 'view') ? [
                        'label' => 'Results',
                        'value' => 'Open',
                        'route' => route('admin.results'),
                        'icon' => 'bx bxs-bar-chart-alt-2',
                        'tone' => 'tone-violet',
                        'is_action' => true,
                    ] : null,
                ]),
            ],
            [
                'title' => 'Administration',
                'subtitle' => 'Staff and system access',
                'tiles' => array_filter([
                    getPermissions('admins', 'view') ? [
                        'label' => 'Admins / Staff',
                        'value' => $admins,
                        'route' => route('admin.admins'),
                        'icon' => 'bx bxs-user-badge',
                        'tone' => 'tone-indigo',
                    ] : null,
                ]),
            ],
        ];
    @endphp

    @foreach ($dashboardSections as $section)
        @if (count($section['tiles']))
            <section class="dash-section mb-4">
                <div class="dash-section-head mb-3">
                    <h5 class="dash-section-title mb-0">{{ $section['title'] }}</h5>
                    <p class="dash-section-subtitle mb-0">{{ $section['subtitle'] }}</p>
                </div>

                <div class="row g-3 g-lg-4">
                    @foreach ($section['tiles'] as $tile)
                        <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                            <a href="{{ $tile['route'] }}" class="dash-tile {{ $tile['tone'] }}">
                                <div class="dash-tile-icon">
                                    <i class="{{ $tile['icon'] }}"></i>
                                </div>
                                <div class="dash-tile-body">
                                    <span class="dash-tile-label">{{ $tile['label'] }}</span>
                                    <span class="dash-tile-value">
                                        @if (!empty($tile['is_action']))
                                            {{ $tile['value'] }}
                                            <i class="bx bx-right-arrow-alt ms-1"></i>
                                        @else
                                            {{ number_format((int) $tile['value']) }}
                                        @endif
                                    </span>
                                </div>
                                <span class="dash-tile-arrow">
                                    <i class="bx bx-chevron-right"></i>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    <style>
        .admin-dashboard {
            --dash-radius: 1rem;
            --dash-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            --dash-shadow-hover: 0 16px 40px rgba(15, 23, 42, 0.14);
        }

        .dash-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.5rem 1.75rem;
            border-radius: calc(var(--dash-radius) + 0.25rem);
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #38bdf8 100%);
            color: #fff;
            box-shadow: var(--dash-shadow);
            overflow: hidden;
            position: relative;
        }

        .dash-hero::after {
            content: "";
            position: absolute;
            inset: auto -2rem -3rem auto;
            width: 10rem;
            height: 10rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .dash-hero-content {
            position: relative;
            z-index: 1;
        }

        .dash-hero-kicker {
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.85;
        }

        .dash-hero-title {
            font-weight: 700;
            color: #fff;
        }

        .dash-hero-text {
            opacity: 0.92;
            max-width: 36rem;
        }

        .dash-hero-badge {
            position: relative;
            z-index: 1;
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 1.25rem;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.14);
            font-size: 2rem;
        }

        .dash-section-head {
            padding-bottom: 0.35rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }

        .dash-section-title {
            font-weight: 700;
            color: #0f172a;
        }

        .dash-section-subtitle {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.15rem;
        }

        .dash-tile {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-height: 6.5rem;
            padding: 1.1rem 1.15rem;
            border-radius: var(--dash-radius);
            text-decoration: none;
            color: inherit;
            background: #fff;
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            height: 100%;
        }

        .dash-tile:hover {
            transform: translateY(-4px);
            box-shadow: var(--dash-shadow-hover);
            border-color: rgba(148, 163, 184, 0.28);
            color: inherit;
        }

        .dash-tile-icon {
            flex: 0 0 3.25rem;
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.55rem;
            color: #fff;
        }

        .dash-tile-body {
            flex: 1 1 auto;
            min-width: 0;
        }

        .dash-tile-label {
            display: block;
            font-size: 0.84rem;
            color: #64748b;
            margin-bottom: 0.2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dash-tile-value {
            display: block;
            font-size: 1.55rem;
            font-weight: 700;
            line-height: 1.1;
            color: #0f172a;
        }

        .dash-tile-arrow {
            flex: 0 0 auto;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: #64748b;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .dash-tile:hover .dash-tile-arrow {
            background: #eef2ff;
            color: #2563eb;
        }

        .tone-blue .dash-tile-icon { background: linear-gradient(135deg, #2563eb, #60a5fa); }
        .tone-rose .dash-tile-icon { background: linear-gradient(135deg, #e11d48, #fb7185); }
        .tone-green .dash-tile-icon { background: linear-gradient(135deg, #059669, #34d399); }
        .tone-slate .dash-tile-icon { background: linear-gradient(135deg, #334155, #64748b); }
        .tone-cyan .dash-tile-icon { background: linear-gradient(135deg, #0891b2, #22d3ee); }
        .tone-amber .dash-tile-icon { background: linear-gradient(135deg, #d97706, #fbbf24); }
        .tone-violet .dash-tile-icon { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .tone-indigo .dash-tile-icon { background: linear-gradient(135deg, #4338ca, #818cf8); }

        @media (max-width: 575.98px) {
            .dash-hero {
                padding: 1.25rem 1.15rem;
            }

            .dash-hero-title {
                font-size: 1.25rem;
            }

            .dash-tile {
                min-height: auto;
            }

            .dash-tile-value {
                font-size: 1.35rem;
            }
        }
    </style>
</div>
