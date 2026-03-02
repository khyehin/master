<x-app-layout>
    <x-slot name="header">
        {{ __('Companies') }}
    </x-slot>
    <style>
        /* Companies 列表页样式（内联保证覆盖 layout，与 report Transactions 同款） */
        .companies-list-page .companies-list-eyebrow {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }
        .companies-list-page .companies-list-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin: 0.25rem 0 0 0;
            line-height: 1.3;
        }
        .companies-list-page .companies-list-msg {
            font-size: 0.875rem;
            line-height: 1.4;
            margin-bottom: 0.75rem;
        }
        .companies-list-page .companies-list-msg--success { color: #166534; }
        .companies-list-page .companies-list-msg--error { color: #b91c1c; }
        .companies-list-page .companies-list-btn {
            font-size: 0.875rem;
            font-weight: 600;
            height: 2.25rem;
            padding: 0 1rem;
            min-width: auto;
            border-radius: 6px;
        }
        .companies-list-page .user-list-table.companies-list-table {
            font-size: 0.875rem;
            line-height: 1.4;
        }
        .companies-list-page .user-list-table.companies-list-table .user-th {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6b7280;
            padding: 0.625rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .companies-list-page .user-list-table.companies-list-table .user-td {
            font-size: 0.875rem;
            color: #374151;
            padding: 0.625rem 1rem;
            vertical-align: middle;
        }
        .companies-list-page .user-list-table.companies-list-table .user-td:first-child {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }
        .companies-list-page .user-list-table .user-td-empty {
            font-size: 0.875rem;
            color: #6b7280;
            padding: 1.5rem 1rem;
        }
        .companies-list-page .user-list-table .user-td-empty a {
            font-weight: 600;
            color: #111827;
        }
        /* 包裹表格的容器：只做左右滚动，垂直方向允许内容溢出（让下拉菜单可以“飞出”表格区域） */
        .companies-list-page .companies-table-scroll {
            overflow-x: auto;
            overflow-y: visible;
        }
        /* 下拉：浮层 box + hover 高亮；挂到全局 portal，避免被任何容器裁切 */
        .companies-list-page .user-actions-dropdown {
            position: fixed !important;
            right: auto !important;
            top: auto !important;
            left: auto !important;
            background: #fff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 10px !important;
            box-shadow: 0 12px 28px rgba(0,0,0,0.15), 0 6px 12px rgba(0,0,0,0.08) !important;
            padding: 6px 0 !important;
            max-height: 220px !important;
            overflow-y: auto !important;
            z-index: 9999 !important;
        }
        .companies-list-page .user-actions-dropdown a {
            display: block !important;
            padding: 8px 14px !important;
            background: transparent !important;
            transition: background 0.15s ease !important;
        }
        .companies-list-page .user-actions-dropdown a:hover {
            background: #e5e7eb !important;
        }
        .companies-list-page .user-actions-dropdown .user-actions-item--danger {
            background: transparent !important;
            transition: background 0.15s ease !important;
        }
        .companies-list-page .user-actions-dropdown .user-actions-item--danger:hover {
            background: #fee2e2 !important;
        }
    </style>
    <div class="max-w-7xl mx-auto w-full pb-12 companies-list-page">
        @if(session('success'))
            <p class="companies-list-msg companies-list-msg--success">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="companies-list-msg companies-list-msg--error">{{ session('error') }}</p>
        @endif

        <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
            <div>
                <div class="companies-list-eyebrow">{{ __('Company') }}</div>
                <h1 class="companies-list-title">{{ __('Companies') }}</h1>
            </div>
            @if($canManage)
                <a href="{{ route('companies.create') }}" class="user-btn user-btn--primary companies-list-btn">{{ __('+ Add new company') }}</a>
            @endif
        </div>

        <div class="companies-table-scroll rounded-xl mt-6">
            <table class="user-list-table w-full min-w-[520px] companies-list-table">
                <thead>
                    <tr>
                        <th class="user-th">{{ __('Name') }}</th>
                        <th class="user-th">{{ __('Code') }}</th>
                        <th class="user-th">{{ __('Base currency') }}</th>
                        <th class="user-th" style="width: 6rem;">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $c)
                        <tr>
                            <td class="user-td">{{ $c->name }}</td>
                            <td class="user-td">{{ $c->code }}</td>
                            <td class="user-td">{{ strtoupper($c->base_currency ?? 'USD') }}</td>
                            <td class="user-td">
                                <div class="user-actions-wrap"
                                     x-data="{
                                        open: false,
                                        menuStyles: '',
                                        reposition() {
                                            this.$nextTick(() => {
                                                const trigger = this.$el.querySelector('.user-actions-trigger');
                                                const menu = this.$refs.menu;
                                                if (!trigger || !menu) return;
                                                const rect = trigger.getBoundingClientRect();
                                                const menuRect = menu.getBoundingClientRect();
                                                const padding = 8;
                                                let top = rect.bottom + 8;
                                                let left = rect.right - menuRect.width;
                                                if (top + menuRect.height > window.innerHeight - padding && rect.top - menuRect.height - 8 > padding) {
                                                    top = rect.top - menuRect.height - 8;
                                                }
                                                if (left + menuRect.width > window.innerWidth - padding) {
                                                    left = window.innerWidth - menuRect.width - padding;
                                                }
                                                if (left < padding) left = padding;
                                                this.menuStyles = `top:${top}px; left:${left}px;`;
                                            });
                                        },
                                        toggle() {
                                            this.open = !this.open;
                                            if (this.open) {
                                                this.reposition();
                                            }
                                        }
                                     }"
                                     @resize.window="open && reposition()">
                                    <button type="button"
                                            class="user-actions-trigger"
                                            @click="toggle()"
                                            aria-haspopup="true"
                                            :aria-expanded="open"
                                            aria-label="{{ __('Actions') }}"><span>⋯</span></button>
                                    <div class="user-actions-dropdown"
                                         x-ref="menu"
                                         x-teleport="#drp-portal"
                                         x-show="open"
                                         :style="menuStyles"
                                         @click.outside="open = false"
                                         @keydown.escape.window="open = false"
                                         x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-100"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95">
                                        <a href="{{ route('companies.report', $c->id) }}">{{ __('Transactions') }}</a>
                                        <a href="{{ route('cashflow.index', ['company_id' => $c->id]) }}">{{ __('Cashflow') }}</a>
                                        @if($canManage)
                                            <a href="{{ route('companies.edit', $c->id) }}">{{ __('Edit') }}</a>
                                            <div class="user-actions-divider"></div>
                                            <form method="post" action="{{ route('companies.destroy', $c->id) }}" class="user-actions-delete-form" onsubmit="return confirm('{{ __('Delete this company?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="user-actions-item--danger">{{ __('Delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="user-row--empty">
                            <td colspan="4" class="user-td-empty">
                                {{ __('No companies yet.') }} @if($canManage) <a href="{{ route('companies.create') }}" class="text-gray-700 underline">{{ __('Add new company') }}</a> @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
