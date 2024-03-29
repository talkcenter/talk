import app from '../../site/app';
import Component from '../../common/Component';
import humanTime from '../../common/utils/humanTime';
import ItemList from '../../common/utils/ItemList';
import UserControls from '../utils/UserControls';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import icon from '../../common/helpers/icon';
import Dropdown from '../../common/components/Dropdown';
import Link from '../../common/components/Link';
import AvatarEditor from './AvatarEditor';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';

/**
 * The `UserCard` component displays a user's profile card. This is used both on
 * the `UserPage` (in the hero) and in discussions, shown when hovering over a
 * post author.
 *
 * ### Attrs
 *
 * - `user`
 * - `className`
 * - `editable`
 * - `controlsButtonClassName`
 */
export default class UserCard extends Component {
  view() {
    const user = this.attrs.user;
    const controls = UserControls.controls(user, this).toArray();
    const color = user.color();
    const badges = user.badges().toArray();

    return (
      <div className={classList('UserCard', this.attrs.className)} style={color && { '--usercard-bg': color }}>
        <div className="darkenBackground">
          <div className="container">
            {!!controls.length && (
              <Dropdown
                className="UserCard-controls App-primaryControl"
                menuClassName="Dropdown-menu--right"
                buttonClassName={this.attrs.controlsButtonClassName}
                label={app.translator.trans('talk.site.user_controls.button')}
                accessibleToggleLabel={app.translator.trans('talk.site.user_controls.toggle_dropdown_accessible_label')}
                icon="fas fa-ellipsis-v"
              >
                {controls}
              </Dropdown>
            )}

            <div className="UserCard-profile">
              <h1 className="UserCard-identity">
                {this.attrs.editable ? (
                  <>
                    <AvatarEditor user={user} className="UserCard-avatar" /> {username(user)}
                  </>
                ) : (
                  <Link href={app.route.user(user)}>
                    <div className="UserCard-avatar">{avatar(user, { loading: 'eager' })}</div>
                    {username(user)}
                  </Link>
                )}
              </h1>

              {!!badges.length && <ul className="UserCard-badges badges">{listItems(badges)}</ul>}

              <ul className="UserCard-info">{listItems(this.infoItems().toArray())}</ul>
            </div>
          </div>
        </div>
      </div>
    );
  }

  /**
   * Build an item list of tidbits of info to show on this user's profile.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  infoItems() {
    const items = new ItemList();
    const user = this.attrs.user;
    const lastSeenAt = user.lastSeenAt();

    if (lastSeenAt) {
      const online = user.isOnline();

      items.add(
        'lastSeen',
        <span className={classList('UserCard-lastSeen', { online })}>
          {online
            ? [icon('fas fa-circle'), ' ', app.translator.trans('talk.site.user.online_text')]
            : [icon('far fa-clock'), ' ', humanTime(lastSeenAt)]}
        </span>,
        100
      );
    }

    items.add('joined', app.translator.trans('talk.site.user.joined_date_text', { ago: humanTime(user.joinTime()) }), 90);

    return items;
  }
}
