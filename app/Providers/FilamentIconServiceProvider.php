<?php

namespace App\Providers;

use Filament\Actions\View\ActionsIconAlias;
use Filament\Forms\View\FormsIconAlias;
use Filament\Infolists\View\InfolistsIconAlias;
use Filament\Notifications\View\NotificationsIconAlias;
use Filament\QueryBuilder\View\QueryBuilderIconAlias;
use Filament\Schemas\View\SchemaIconAlias;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\View\SupportIconAlias;
use Filament\Tables\View\TablesIconAlias;
use Filament\View\PanelsIconAlias;
use Filament\Widgets\View\WidgetsIconAlias;
use Illuminate\Support\ServiceProvider;

/**
 * Replaces every default Filament (Heroicon) icon with its Lucide equivalent.
 */
class FilamentIconServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        FilamentIcon::register([
            ...$this->actionIcons(),
            ...$this->formIcons(),
            ...$this->infolistIcons(),
            ...$this->notificationIcons(),
            ...$this->panelIcons(),
            ...$this->queryBuilderIcons(),
            ...$this->schemaIcons(),
            ...$this->tableIcons(),
            ...$this->supportIcons(),
            ...$this->widgetIcons(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function actionIcons(): array
    {
        return [
            ActionsIconAlias::ACTION_GROUP => 'lucide-ellipsis-vertical',
            ActionsIconAlias::CREATE_ACTION_GROUPED => 'lucide-plus',
            ActionsIconAlias::DELETE_ACTION => 'lucide-trash-2',
            ActionsIconAlias::DELETE_ACTION_GROUPED => 'lucide-trash-2',
            ActionsIconAlias::DELETE_ACTION_MODAL => 'lucide-trash-2',
            ActionsIconAlias::DETACH_ACTION => 'lucide-x',
            ActionsIconAlias::DETACH_ACTION_MODAL => 'lucide-x',
            ActionsIconAlias::DISSOCIATE_ACTION => 'lucide-x',
            ActionsIconAlias::DISSOCIATE_ACTION_MODAL => 'lucide-x',
            ActionsIconAlias::EDIT_ACTION => 'lucide-square-pen',
            ActionsIconAlias::EDIT_ACTION_GROUPED => 'lucide-square-pen',
            ActionsIconAlias::EXPORT_ACTION_GROUPED => 'lucide-download',
            ActionsIconAlias::FORCE_DELETE_ACTION => 'lucide-trash-2',
            ActionsIconAlias::FORCE_DELETE_ACTION_GROUPED => 'lucide-trash-2',
            ActionsIconAlias::FORCE_DELETE_ACTION_MODAL => 'lucide-trash-2',
            ActionsIconAlias::IMPORT_ACTION_GROUPED => 'lucide-upload',
            ActionsIconAlias::MODAL_CONFIRMATION => 'lucide-triangle-alert',
            ActionsIconAlias::REPLICATE_ACTION => 'lucide-copy',
            ActionsIconAlias::REPLICATE_ACTION_GROUPED => 'lucide-copy',
            ActionsIconAlias::RESTORE_ACTION => 'lucide-undo-2',
            ActionsIconAlias::RESTORE_ACTION_GROUPED => 'lucide-undo-2',
            ActionsIconAlias::RESTORE_ACTION_MODAL => 'lucide-undo-2',
            ActionsIconAlias::VIEW_ACTION => 'lucide-eye',
            ActionsIconAlias::VIEW_ACTION_GROUPED => 'lucide-eye',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function formIcons(): array
    {
        return [
            FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_CLONE => 'lucide-copy',
            FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_COLLAPSE => 'lucide-chevron-up',
            FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_DELETE => 'lucide-trash-2',
            FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_EDIT => 'lucide-settings',
            FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_EXPAND => 'lucide-chevron-down',
            FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_MOVE_DOWN => 'lucide-arrow-down',
            FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_MOVE_UP => 'lucide-arrow-up',
            FormsIconAlias::COMPONENTS_BUILDER_ACTIONS_REORDER => 'lucide-arrow-up-down',
            FormsIconAlias::COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD => 'lucide-search',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_CROP => 'lucide-crop',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_DRAG_MOVE => 'lucide-move',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_FLIP_HORIZONTAL => 'lucide-flip-horizontal-2',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_FLIP_VERTICAL => 'lucide-flip-vertical-2',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_DOWN => 'lucide-circle-arrow-down',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_LEFT => 'lucide-circle-arrow-left',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_RIGHT => 'lucide-circle-arrow-right',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_MOVE_UP => 'lucide-circle-arrow-up',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ROTATE_LEFT => 'lucide-rotate-ccw',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ROTATE_RIGHT => 'lucide-rotate-cw',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_100 => 'lucide-expand',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_IN => 'lucide-zoom-in',
            FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_OUT => 'lucide-zoom-out',
            FormsIconAlias::COMPONENTS_KEY_VALUE_ACTIONS_DELETE => 'lucide-trash-2',
            FormsIconAlias::COMPONENTS_KEY_VALUE_ACTIONS_REORDER => 'lucide-arrow-up-down',
            FormsIconAlias::COMPONENTS_MODAL_TABLE_SELECT_ACTIONS_SELECT => 'lucide-square-pen',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_CLONE => 'lucide-copy',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_COLLAPSE => 'lucide-chevron-up',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_DELETE => 'lucide-trash-2',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_EXPAND => 'lucide-chevron-down',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_MOVE_DOWN => 'lucide-arrow-down',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_MOVE_UP => 'lucide-arrow-up',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_REORDER => 'lucide-arrow-up-down',
            FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCKS_CLOSE_BUTTON => 'lucide-x',
            FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCK_DELETE_BUTTON => 'lucide-trash-2',
            FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCK_EDIT_BUTTON => 'lucide-square-pen',
            FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_MERGE_TAGS_CLOSE_BUTTON => 'lucide-x',
            FormsIconAlias::COMPONENTS_SELECT_ACTIONS_CREATE_OPTION => 'lucide-plus',
            FormsIconAlias::COMPONENTS_SELECT_ACTIONS_EDIT_OPTION => 'lucide-square-pen',
            FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_COPY => 'lucide-clipboard-copy',
            FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_HIDE_PASSWORD => 'lucide-eye-off',
            FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_SHOW_PASSWORD => 'lucide-eye',
            FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_FALSE => 'lucide-x',
            FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE => 'lucide-check',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function infolistIcons(): array
    {
        return [
            InfolistsIconAlias::COMPONENTS_ICON_ENTRY_FALSE => 'lucide-circle-x',
            InfolistsIconAlias::COMPONENTS_ICON_ENTRY_TRUE => 'lucide-circle-check',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function notificationIcons(): array
    {
        return [
            NotificationsIconAlias::DATABASE_MODAL_EMPTY_STATE => 'lucide-bell-off',
            NotificationsIconAlias::NOTIFICATION_CLOSE_BUTTON => 'lucide-x',
            NotificationsIconAlias::NOTIFICATION_DANGER => 'lucide-circle-x',
            NotificationsIconAlias::NOTIFICATION_INFO => 'lucide-info',
            NotificationsIconAlias::NOTIFICATION_SUCCESS => 'lucide-circle-check',
            NotificationsIconAlias::NOTIFICATION_WARNING => 'lucide-circle-alert',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function panelIcons(): array
    {
        return [
            ...$this->multiFactorAuthenticationIcons(),
            PanelsIconAlias::GLOBAL_SEARCH_FIELD => 'lucide-search',
            PanelsIconAlias::PAGES_DASHBOARD_ACTIONS_FILTER => 'lucide-funnel',
            PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM => 'lucide-house',
            PanelsIconAlias::PAGES_PASSWORD_RESET_REQUEST_PASSWORD_RESET_ACTIONS_LOGIN => 'lucide-arrow-left',
            PanelsIconAlias::PAGES_PASSWORD_RESET_REQUEST_PASSWORD_RESET_ACTIONS_LOGIN_RTL => 'lucide-arrow-right',
            PanelsIconAlias::RESOURCES_PAGES_EDIT_RECORD_NAVIGATION_ITEM => 'lucide-square-pen',
            PanelsIconAlias::RESOURCES_PAGES_MANAGE_RELATED_RECORDS_NAVIGATION_ITEM => 'lucide-layers',
            PanelsIconAlias::RESOURCES_PAGES_VIEW_RECORD_NAVIGATION_ITEM => 'lucide-eye',
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => 'lucide-chevron-left',
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => 'lucide-chevron-right',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => 'lucide-chevron-right',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => 'lucide-chevron-left',
            PanelsIconAlias::SIDEBAR_GROUP_COLLAPSE_BUTTON => 'lucide-chevron-up',
            PanelsIconAlias::SIDEBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON => 'lucide-bell',
            PanelsIconAlias::SUB_NAVIGATION_MOBILE_MENU_BUTTON => 'lucide-chevron-down',
            PanelsIconAlias::TENANT_MENU_BILLING_BUTTON => 'lucide-credit-card',
            PanelsIconAlias::TENANT_MENU_PROFILE_BUTTON => 'lucide-settings',
            PanelsIconAlias::TENANT_MENU_REGISTRATION_BUTTON => 'lucide-plus',
            PanelsIconAlias::TENANT_MENU_TOGGLE_BUTTON => 'lucide-chevron-down',
            PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON => 'lucide-sun',
            PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON => 'lucide-moon',
            PanelsIconAlias::THEME_SWITCHER_SYSTEM_BUTTON => 'lucide-monitor',
            PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON => 'lucide-x',
            PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON => 'lucide-menu',
            PanelsIconAlias::TOPBAR_GROUP_TOGGLE_BUTTON => 'lucide-chevron-down',
            PanelsIconAlias::TOPBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON => 'lucide-bell',
            PanelsIconAlias::USER_MENU_PROFILE_ITEM => 'lucide-circle-user',
            PanelsIconAlias::USER_MENU_LOGOUT_BUTTON => 'lucide-log-out',
            PanelsIconAlias::USER_MENU_TOGGLE_BUTTON => 'lucide-chevron-up',
            PanelsIconAlias::WIDGETS_ACCOUNT_LOGOUT_BUTTON => 'lucide-log-out',
            PanelsIconAlias::WIDGETS_FILAMENT_INFO_OPEN_DOCUMENTATION_BUTTON => 'lucide-book-open',
            PanelsIconAlias::WIDGETS_FILAMENT_INFO_OPEN_GITHUB_BUTTON => 'lucide-github',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function multiFactorAuthenticationIcons(): array
    {
        return [
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_DISABLE => 'lucide-lock-open',
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_DISABLE_MODAL => 'lucide-lock-open',
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_DISABLE_NOTIFICATION => 'lucide-lock-open',
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_REGENERATE_RECOVERY_CODES => 'lucide-refresh-cw',
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_REGENERATE_RECOVERY_CODES_MODAL => 'lucide-refresh-cw',
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_REGENERATE_RECOVERY_CODES_NOTIFICATION => 'lucide-refresh-cw',
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_SET_UP => 'lucide-lock',
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_SET_UP_MODAL => 'lucide-lock',
            PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_SET_UP_NOTIFICATION => 'lucide-lock',
            PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_DISABLE => 'lucide-lock-open',
            PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_DISABLE_MODAL => 'lucide-lock-open',
            PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_DISABLE_NOTIFICATION => 'lucide-lock-open',
            PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP => 'lucide-lock',
            PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP_MODAL => 'lucide-lock',
            PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP_NOTIFICATION => 'lucide-lock',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function queryBuilderIcons(): array
    {
        return [
            QueryBuilderIconAlias::ADD_RULE_ACTION => 'lucide-plus',
            QueryBuilderIconAlias::CONSTRAINTS_BOOLEAN => 'lucide-circle-check',
            QueryBuilderIconAlias::CONSTRAINTS_DATE => 'lucide-calendar',
            QueryBuilderIconAlias::CONSTRAINTS_NUMBER => 'lucide-variable',
            QueryBuilderIconAlias::CONSTRAINTS_RELATIONSHIP => 'lucide-expand',
            QueryBuilderIconAlias::CONSTRAINTS_SELECT => 'lucide-chevrons-up-down',
            QueryBuilderIconAlias::CONSTRAINTS_TEXT => 'lucide-languages',
            QueryBuilderIconAlias::OR_GROUP_ADD_GROUP_ACTION => 'lucide-plus',
            QueryBuilderIconAlias::OR_GROUP_BLOCK => 'lucide-slash',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function schemaIcons(): array
    {
        return [
            SchemaIconAlias::COMPONENTS_CALLOUT_DANGER => 'lucide-circle-x',
            SchemaIconAlias::COMPONENTS_CALLOUT_INFO => 'lucide-info',
            SchemaIconAlias::COMPONENTS_CALLOUT_SUCCESS => 'lucide-circle-check',
            SchemaIconAlias::COMPONENTS_CALLOUT_WARNING => 'lucide-circle-alert',
            SchemaIconAlias::COMPONENTS_TABS_DROPDOWN_TRIGGER_BUTTON => 'lucide-chevron-down',
            SchemaIconAlias::COMPONENTS_TABS_MORE_TABS_BUTTON => 'lucide-ellipsis',
            SchemaIconAlias::COMPONENTS_WIZARD_COMPLETED_STEP => 'lucide-check',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function tableIcons(): array
    {
        return [
            TablesIconAlias::ACTIONS_COLUMN_MANAGER => 'lucide-columns-3',
            TablesIconAlias::ACTIONS_DISABLE_REORDERING => 'lucide-check',
            TablesIconAlias::ACTIONS_ENABLE_REORDERING => 'lucide-arrow-up-down',
            TablesIconAlias::ACTIONS_FILTER => 'lucide-funnel',
            TablesIconAlias::ACTIONS_GROUP => 'lucide-layers',
            TablesIconAlias::ACTIONS_OPEN_BULK_ACTIONS => 'lucide-ellipsis-vertical',
            TablesIconAlias::COLUMNS_COLLAPSE_BUTTON => 'lucide-chevron-down',
            TablesIconAlias::COLUMNS_ICON_COLUMN_FALSE => 'lucide-circle-x',
            TablesIconAlias::COLUMNS_ICON_COLUMN_TRUE => 'lucide-circle-check',
            TablesIconAlias::EMPTY_STATE => 'lucide-x',
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_BOOLEAN => 'lucide-circle-check',
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_DATE => 'lucide-calendar',
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_NUMBER => 'lucide-variable',
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_RELATIONSHIP => 'lucide-expand',
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_SELECT => 'lucide-chevrons-up-down',
            TablesIconAlias::FILTERS_QUERY_BUILDER_CONSTRAINTS_TEXT => 'lucide-languages',
            TablesIconAlias::FILTERS_REMOVE_ALL_BUTTON => 'lucide-x',
            TablesIconAlias::GROUPING_COLLAPSE_BUTTON => 'lucide-chevron-up',
            TablesIconAlias::HEADER_CELL_SORT_ASC_BUTTON => 'lucide-chevron-up',
            TablesIconAlias::HEADER_CELL_SORT_BUTTON => 'lucide-chevron-down',
            TablesIconAlias::HEADER_CELL_SORT_DESC_BUTTON => 'lucide-chevron-down',
            TablesIconAlias::REORDER_HANDLE => 'lucide-grip-vertical',
            TablesIconAlias::SEARCH_FIELD => 'lucide-search',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function supportIcons(): array
    {
        return [
            SupportIconAlias::BADGE_DELETE_BUTTON => 'lucide-x',
            SupportIconAlias::BREADCRUMBS_SEPARATOR => 'lucide-chevron-right',
            SupportIconAlias::BREADCRUMBS_SEPARATOR_RTL => 'lucide-chevron-left',
            SupportIconAlias::MODAL_CLOSE_BUTTON => 'lucide-x',
            SupportIconAlias::PAGINATION_FIRST_BUTTON => 'lucide-chevrons-left',
            SupportIconAlias::PAGINATION_FIRST_BUTTON_RTL => 'lucide-chevrons-right',
            SupportIconAlias::PAGINATION_LAST_BUTTON => 'lucide-chevrons-right',
            SupportIconAlias::PAGINATION_LAST_BUTTON_RTL => 'lucide-chevrons-left',
            SupportIconAlias::PAGINATION_NEXT_BUTTON => 'lucide-chevron-right',
            SupportIconAlias::PAGINATION_NEXT_BUTTON_RTL => 'lucide-chevron-left',
            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON => 'lucide-chevron-left',
            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON_RTL => 'lucide-chevron-right',
            SupportIconAlias::SECTION_COLLAPSE_BUTTON => 'lucide-chevron-up',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function widgetIcons(): array
    {
        return [
            WidgetsIconAlias::CHART_WIDGET_EMPTY_STATE => 'lucide-x',
            WidgetsIconAlias::CHART_WIDGET_FILTER => 'lucide-funnel',
        ];
    }
}
