import { register } from '../../core/page-registry';

import BlogCreate from './blog/create';
import BlogEdit from './blog/edit';
import BlogIndex from './blog/index';

import ProjectsCreate from './projects/create';
import ProjectsEdit from './projects/edit';
import ProjectsIndex from './projects/index';
import ProjectsTrash from './projects/trash';

import PermissionsCreate from './permissions/create';
import PermissionsEdit from './permissions/edit';
import PermissionsIndex from './permissions/index';

import RolesCreate from './roles/create';
import RolesEdit from './roles/edit';
import RolesIndex from './roles/index';

import UsersCreate from './users/create';
import UsersEdit from './users/edit';
import UsersIndex from './users/index';

import CategoriesCreate from './categories/create';
import CategoriesEdit from './categories/edit';
import CategoriesIndex from './categories/index';
import DashIndex from './dash/index';

import profileEdit from './profile/edit';
import profileIndex from './profile/index';

import mediaIndex from './media/index';
import trashIndex from './trash/index';

import galleriesIndex from './galleries/index';
import galleriesCreate from './galleries/create';
import galleriesEdit from './galleries/edit';

import ProductsCreate from './products/create';
import ProductsEdit from './products/edit';
import ProductsIndex from './products/index';
import ProductsTrash from './products/trash';

import AppointmentsCalendar from './appointments/calendar';
import AppointmentsSettings from './appointments/settings';
import MessagesIndex from './messages/index';
import MembersIndex from './members/index';
import MembersShow from './members/show';
import SitePagesCreate from './site/pages/create';
import SitePagesEdit from './site/pages/edit';
import SitePagesIndex from './site/pages/index';
import SiteLanguagesIndex from './site/languages/index';
import SiteFaqsIndex from './site/faqs/index';
import SiteCountersIndex from './site/counters/index';
import SiteNavigationIndex from './site/navigation/index';
import SiteSettingsEdit from './site/settings/edit';
import SiteSlidersCreate from './site/sliders/create';
import SiteSlidersEdit from './site/sliders/edit';
import SiteSlidersIndex from './site/sliders/index';


export function registerPages() {
    register('dash.index', DashIndex);

    register('blog.create', BlogCreate);
    register('blog.edit', BlogEdit);
    register('blog.index', BlogIndex);

    register('projects.create', ProjectsCreate);
    register('projects.edit', ProjectsEdit);
    register('projects.index', ProjectsIndex);
    register('projects.trash', ProjectsTrash);

    register('permissions.create', PermissionsCreate);
    register('permissions.edit', PermissionsEdit);
    register('permissions.index', PermissionsIndex);

    register('roles.create', RolesCreate);
    register('roles.edit', RolesEdit);
    register('roles.index', RolesIndex);

    register('users.create', UsersCreate);
    register('users.edit', UsersEdit);
    register('users.index', UsersIndex);

    register('categories.create', CategoriesCreate);
    register('categories.edit', CategoriesEdit);
    register('categories.index', CategoriesIndex);

    register('profile.edit', profileEdit);
    register('profile.index', profileIndex);

    register('media.index', mediaIndex);
    register('trash.index', trashIndex);

    register('galleries.index', galleriesIndex);
    register('galleries.create', galleriesCreate);
    register('galleries.edit', galleriesEdit);

    register('products.create', ProductsCreate);
    register('products.edit', ProductsEdit);
    register('products.index', ProductsIndex);
    register('products.trash', ProductsTrash);

    register('appointments.calendar', AppointmentsCalendar);
    register('appointments.settings', AppointmentsSettings);
    register('messages.index', MessagesIndex);
    register('members.index', MembersIndex);
    register('members.show', MembersShow);

    register('site.languages.index', SiteLanguagesIndex);
    register('site.pages.index', SitePagesIndex);
    register('site.pages.create', SitePagesCreate);
    register('site.pages.edit', SitePagesEdit);
    register('site.faqs.index', SiteFaqsIndex);
    register('site.counters.index', SiteCountersIndex);
    register('site.navigation.index', SiteNavigationIndex);
    register('site.settings.edit', SiteSettingsEdit);
    register('site.sliders.index', SiteSlidersIndex);
    register('site.sliders.create', SiteSlidersCreate);
    register('site.sliders.edit', SiteSlidersEdit);
}
