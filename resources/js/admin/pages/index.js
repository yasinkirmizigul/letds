import { register } from '../../core/page-registry';

import BlogCreate from './blog/create';
import BlogEdit from './blog/edit';
import BlogIndex from './blog/index';
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
import profileEdit from './profile/edit';
import profileIndex from './profile/index';
import mediaIndex from './media/index';

export function registerPages() {
    register('blog.create', BlogCreate);
    register('blog.edit', BlogEdit);
    register('blog.index', BlogIndex);

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
}
