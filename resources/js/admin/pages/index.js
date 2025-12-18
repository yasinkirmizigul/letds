import { register } from '../../core/page-registry';

// Blog
import BlogIndex from './blog/index';

export function registerPages() {
    register('blog.index', BlogIndex);
}
