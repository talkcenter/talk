import app from '../common/app';

import ItemList from './utils/ItemList';
import Button from './components/Button';
import ModalManager from './components/ModalManager';
import AlertManager from './components/AlertManager';
import RequestErrorModal from './components/RequestErrorModal';
import Translator from './Translator';
import Store, { ApiPayload, ApiResponse, ApiResponsePlural, ApiResponseSingle, payloadIsPlural } from './Store';
import Session from './Session';
import extract from './utils/extract';
import extractText from './utils/extractText';
import Drawer from './utils/Drawer';
import mapRoutes from './utils/mapRoutes';
import RequestError, { InternalTalkRequestOptions } from './utils/RequestError';
import ScrollListener from './utils/ScrollListener';
import liveHumanTimes from './utils/liveHumanTimes';
// @ts-expect-error 我们需要显式使用前缀来区分 extend 文件夹。
import { extend } from './extend.ts';

import Site from './models/Site';
import User from './models/User';
import Discussion from './models/Discussion';
import Post from './models/Post';
import Group from './models/Group';
import Notification from './models/Notification';
import PageState from './states/PageState';
import ModalManagerState from './states/ModalManagerState';
import AlertManagerState from './states/AlertManagerState';

import type DefaultResolver from './resolvers/DefaultResolver';
import type Mithril from 'mithril';
import type Component from './Component';
import type { ComponentAttrs } from './Component';
import Model, { SavedModelData } from './Model';
import fireApplicationError from './helpers/fireApplicationError';
import IHistory from './IHistory';
import IExtender from './extenders/IExtender';
import AccessToken from './models/AccessToken';

export type TalkScreens = 'phone' | 'tablet' | 'desktop' | 'desktop-hd';

export type TalkGenericRoute = RouteItem<any, any, any>;

export interface TalkRequestOptions<ResponseType> extends Omit<Mithril.RequestOptions<ResponseType>, 'extract'> {
  errorHandler?: (error: RequestError) => void;
  url: string;

  /**
   * 在将响应文本解析为 JSON 之前对其进行操作。
   *
   * @deprecated 请改用 `modifyText`
   */
  extract?: (responseText: string) => string;
  /**
   * 在将响应文本解析为 JSON 之前对其进行操作。
   *
   * 这将覆盖提供的任何 `extract`方法。
   */
  modifyText?: (responseText: string) => string;
}

/**
 * 有效的路由定义。
 */
export type RouteItem<
  Attrs extends ComponentAttrs,
  Comp extends Component<Attrs & { routeName: string }>,
  RouteArgs extends Record<string, unknown> = {}
> = {
  /**
   * The path for your route.
   *
   * This might be a specific URL path (e.g.,`/myPage`), or it might
   * contain a variable used by a resolver (e.g., `/myPage/:id`).
   *
   */
  path: `/${string}`;
} & (
  | {
      /**
       * The component to render when this route matches.
       */
      component: new () => Comp;
      /**
       * A custom resolver class.
       *
       * This should be the class itself, and **not** an instance of the
       * class.
       */
      resolverClass?: new (component: new () => Comp, routeName: string) => DefaultResolver<Attrs, Comp, RouteArgs>;
    }
  | {
      /**
       * An instance of a route resolver.
       */
      resolver: RouteResolver<Attrs, Comp, RouteArgs>;
    }
);

export interface RouteResolver<
  Attrs extends ComponentAttrs,
  Comp extends Component<Attrs & { routeName: string }>,
  RouteArgs extends Record<string, unknown> = {}
> {
  /**
   * A method which selects which component to render based on
   * conditional logic.
   *
   * Returns the component class, and **not** a Vnode or JSX
   * expression.
   *
   * @see https://mithril.js.org/route.html#routeresolveronmatch
   */
  onmatch(this: this, args: RouteArgs, requestedPath: string, route: string): { new (): Comp };
  /**
   * A function which renders the provided component.
   *
   * If not specified, the route will default to rendering the
   * component on its own, inside of a fragment.
   *
   * Returns a Mithril Vnode or other children.
   *
   * @see https://mithril.js.org/route.html#routeresolverrender
   */
  render?(this: this, vnode: Mithril.Vnode<Attrs, Comp>): Mithril.Children;
}

export interface ApplicationData {
  apiDocument: ApiPayload | null;
  locale: string;
  locales: Record<string, string>;
  resources: SavedModelData[];
  session: { userId: number; csrfToken: string };
  [key: string]: unknown;
}

/**
 * The `App` class provides a container for an application, as well as various
 * utilities for the rest of the app to use.
 */
export default class Application {
  /**
   * The site model for this application.
   */
  site!: Site;

  /**
   * A map of routes, keyed by a unique route name. Each route is an object
   * containing the following properties:
   *
   * - `path` The path that the route is accessed at.
   * - `component` The Mithril component to render when this route is active.
   *
   * @example
   * app.routes.discussion = { path: '/discussion/:id', component: DiscussionPage };
   */
  routes: Record<string, TalkGenericRoute> = {};

  /**
   * An ordered list of initializers to bootstrap the application.
   */
  initializers: ItemList<(app: this) => void> = new ItemList();

  /**
   * The app's session.
   *
   * Stores info about the current user.
   */
  session!: Session;

  /**
   * The app's translator.
   */
  translator: Translator = new Translator();

  /**
   * The app's data store.
   */
  store: Store = new Store({
    'access-tokens': AccessToken,
    sites: Site,
    users: User,
    discussions: Discussion,
    posts: Post,
    groups: Group,
    notifications: Notification,
  });

  /**
   * A local cache that can be used to store data at the application level, so
   * that is persists between different routes.
   */
  cache: Record<string, unknown> = {};

  /**
   * Whether or not the app has been booted.
   */
  booted: boolean = false;

  /**
   * The page the app is currently on.
   *
   * This object holds information about the type of page we are currently
   * visiting, and sometimes additional arbitrary page state that may be
   * relevant to lower-level components.
   */
  current: PageState = new PageState(null);

  /**
   * The page the app was on before the current page.
   *
   * Once the application navigates to another page, the object previously
   * assigned to this.current will be moved to this.previous, while this.current
   * is re-initialized.
   */
  previous: PageState = new PageState(null);

  /**
   * 管理模式状态的对象。
   */
  modal: ModalManagerState = new ModalManagerState();

  /**
   * 一个对象，用于管理活动警报的状态。
   */
  alerts: AlertManagerState = new AlertManagerState();

  /**
   * 一个对象，用于管理导航抽屉的状态。
   */
  drawer!: Drawer;

  history: IHistory | null = null;
  pane: any = null;

  data!: ApplicationData;

  private _title: string = '';
  private _titleCount: number = 0;

  private set title(val: string) {
    this._title = val;
  }

  get title() {
    return this._title;
  }

  private set titleCount(val: number) {
    this._titleCount = val;
  }

  get titleCount() {
    return this._titleCount;
  }

  /**
   * 由于AJAX请求错误而显示的Alert的键。
   * 如果存在，它将在下一次成功请求时被驳回。
   */
  private requestErrorAlert: number | null = null;

  initialRoute!: string;

  public load(payload: Application['data']) {
    this.data = payload;
    this.translator.setLocale(payload.locale);
  }

  public boot() {
    const caughtInitializationErrors: CallableFunction[] = [];

    this.initializers.toArray().forEach((initializer) => {
      try {
        initializer(this);
      } catch (e) {
        const extension = initializer.itemName.includes('/')
          ? initializer.itemName.replace(/(\/talk-ext-)|(\/talk-)/g, '-')
          : initializer.itemName;

        caughtInitializationErrors.push(() =>
          fireApplicationError(
            extractText(app.translator.trans('talk.lib.error.extension_initialiation_failed_message', { extension })),
            `${extension} failed to initialize`,
            e
          )
        );
      }
    });

    this.store.pushPayload({ data: this.data.resources });

    this.site = this.store.getById('sites', '1')!;

    this.session = new Session(this.store.getById<User>('users', String(this.data.session.userId)) ?? null, this.data.session.csrfToken);

    this.mount();

    this.initialRoute = window.location.href;

    caughtInitializationErrors.forEach((handler) => handler());
  }

  public bootExtensions(extensions: Record<string, { extend?: IExtender[] }>) {
    Object.keys(extensions).forEach((name) => {
      const extension = extensions[name];

      // 如果扩展未定义扩展程序，则此处无需执行其他操作。
      if (!extension.extend) return;

      const extenders = extension.extend.flat(Infinity);

      for (const extender of extenders) {
        extender.extend(this, { name, exports: extension });
      }
    });
  }

  protected mount(basePath: string = '') {
    // 使用具有可调用视图属性的对象将参数传递给组件;查看 https://mithril.js.org/mount.html
    m.mount(document.getElementById('modal')!, { view: () => <ModalManager state={this.modal} /> });
    m.mount(document.getElementById('alerts')!, { view: () => <AlertManager state={this.alerts} /> });

    this.drawer = new Drawer();

    m.route(document.getElementById('content')!, basePath + '/', mapRoutes(this.routes, basePath));

    const appEl = document.getElementById('app')!;
    const appHeaderEl = document.querySelector('.App-header')!;

    // 在正文中添加一个类，指示页面已向下滚动。发生这种情况时，我们会将类添加到标头和应用正文中，这会将导航栏的位置设置为固定。
    // 我们不希望总是修复它，因为这可能会与自定义标头重叠。
    const scrollListener = new ScrollListener((top: number) => {
      const offset = appEl.getBoundingClientRect().top + document.body.scrollTop;

      appEl.classList.toggle('affix', top >= offset);
      appEl.classList.toggle('scrolled', top > offset);

      appHeaderEl.classList.toggle('navbar-fixed-top', top >= offset);
    });

    scrollListener.start();
    scrollListener.update();

    document.body.classList.add('ontouchstart' in window ? 'touch' : 'no-touch');

    liveHumanTimes();
  }

  /**
   * 获取已预加载到应用程序中的 API 响应文档。
   */
  preloadedApiDocument<M extends Model>(): ApiResponseSingle<M> | null;
  preloadedApiDocument<Ms extends Model[]>(): ApiResponsePlural<Ms[number]> | null;
  preloadedApiDocument<M extends Model | Model[]>(): ApiResponse<FlatArray<M, 1>> | null {
    // 如果 URL 已更改，则预加载的 Api 文档无效。
    if (this.data.apiDocument && window.location.href === this.initialRoute) {
      const results = payloadIsPlural(this.data.apiDocument)
        ? this.store.pushPayload<FlatArray<M, 1>[]>(this.data.apiDocument)
        : this.store.pushPayload<FlatArray<M, 1>>(this.data.apiDocument);

      this.data.apiDocument = null;

      return results;
    }

    return null;
  }

  /**
   * 根据我们的媒体查询确定当前的屏幕模式。
   */
  screen(): TalkScreens {
    const styles = getComputedStyle(document.documentElement);
    return styles.getPropertyValue('--talk-screen') as ReturnType<Application['screen']>;
  }

  /**
   * 设置页面的 `<title>` 。
   *
   * @param title 新页面标题
   */
  setTitle(title: string): void {
    this.title = title;
    this.updateTitle();
  }

  /**
   * 设置要在页面的 `<title>` 中显示的数字。
   *
   * @param count 标题中显示的数字
   */
  setTitleCount(count: number): void {
    this.titleCount = count;
    this.updateTitle();
  }

  updateTitle(): void {
    const count = this.titleCount ? `(${this.titleCount}) ` : '';
    const onHomepage = m.route.get() === this.site.attribute('basePath') + '/';

    const params = {
      pageTitle: this.title,
      siteName: this.site.attribute('title'),
      // 在我们向前端添加页码之前，这是恒定的 1，因此页码部分不会显示在 URL 中。
      pageNumber: 1,
    };

    let title =
      onHomepage || !this.title
        ? extractText(app.translator.trans('talk.lib.meta_titles.without_page_title', params))
        : extractText(app.translator.trans('talk.lib.meta_titles.with_page_title', params));

    title = count + title;

    // 我们通过 DOMParser 传递标题，以允许正确呈现 HTML 实体，同时仍然通过使用禁用脚本的环境来防止来自用户输入的 XSS 攻击。
    const parser = new DOMParser();
    document.title = parser.parseFromString(title, 'text/html').body.innerText;
  }

  protected transformRequestOptions<ResponseType>(talkOptions: TalkRequestOptions<ResponseType>): InternalTalkRequestOptions<ResponseType> {
    const { background, deserialize, extract, modifyText, ...tmpOptions } = { ...talkOptions };

    // 除非另有规定，否则请求应该在后台异步运行，这样它们就不会阻止重绘的发生。
    const defaultBackground = true;

    // 当我们反序列化JSON数据时，如果由于某种原因服务器提供了一个无效的响应，我们不希望应用程序崩溃。我们将向用户显示一条错误消息。

    const defaultDeserialize = (response: string) => response as ResponseType;

    // 从响应中提取数据时，我们可以检查服务器响应代码，并在出现问题时向用户显示错误消息。
    const originalExtract = modifyText || extract;

    const options: InternalTalkRequestOptions<ResponseType> = {
      background: background ?? defaultBackground,
      deserialize: deserialize ?? defaultDeserialize,
      ...tmpOptions,
    };

    extend(options, 'config', (_: undefined, xhr: XMLHttpRequest) => {
      xhr.setRequestHeader('X-CSRF-Token', this.session.csrfToken!);
    });

    // 如果该方法类似于 PATCH 或 DELETE，并非所有服务器和客户端都支持，那么我们将使用 X-HTTP-Method-Override 标头中指定的预期方法将其作为 POST 请求发送。
    if (options.method && !['GET', 'POST'].includes(options.method)) {
      const method = options.method;

      extend(options, 'config', (_: undefined, xhr: XMLHttpRequest) => {
        xhr.setRequestHeader('X-HTTP-Method-Override', method);
      });

      options.method = 'POST';
    }

    options.extract = (xhr: XMLHttpRequest) => {
      let responseText;

      if (originalExtract) {
        responseText = originalExtract(xhr.responseText);
      } else {
        responseText = xhr.responseText;
      }

      const status = xhr.status;

      if (status < 200 || status > 299) {
        throw new RequestError<ResponseType>(status, `${responseText}`, options, xhr);
      }

      if (xhr.getResponseHeader) {
        const csrfToken = xhr.getResponseHeader('X-CSRF-Token');
        if (csrfToken) app.session.csrfToken = csrfToken;
      }

      try {
        if (responseText === '') {
          return null;
        }

        return JSON.parse(responseText);
      } catch (e) {
        throw new RequestError<ResponseType>(500, `${responseText}`, options, xhr);
      }
    };

    return options;
  }

  /**
   * 发出 AJAX 请求，处理可能发生的任何低级错误。
   *
   * @see https://mithril.js.org/request.html
   */
  request<ResponseType>(originalOptions: TalkRequestOptions<ResponseType>): Promise<ResponseType> {
    const options = this.transformRequestOptions(originalOptions);

    if (this.requestErrorAlert) this.alerts.dismiss(this.requestErrorAlert);

    return m.request(options).catch((e) => this.requestErrorCatch(e, originalOptions.errorHandler));
  }

  /**
   * 默认情况下，显示错误警报，并将错误记录到控制台。
   */
  protected requestErrorCatch<ResponseType>(error: RequestError, customErrorHandler: TalkRequestOptions<ResponseType>['errorHandler']) {
    // 对 details 属性进行解码以转换转义字符，例如 '\n'
    const formattedErrors = error.response?.errors?.map((e) => decodeURI(e.detail ?? '')) ?? [];

    let content;
    switch (error.status) {
      case 422:
        content = formattedErrors
          .map((detail) => [detail, <br />])
          .flat()
          .slice(0, -1);
        break;

      case 401:
      case 403:
        content = app.translator.trans('talk.lib.error.permission_denied_message');
        break;

      case 404:
      case 410:
        content = app.translator.trans('talk.lib.error.not_found_message');
        break;

      case 413:
        content = app.translator.trans('talk.lib.error.payload_too_large_message');
        break;

      case 429:
        content = app.translator.trans('talk.lib.error.rate_limit_exceeded_message');
        break;

      default:
        if (this.requestWasCrossOrigin(error)) {
          content = app.translator.trans('talk.lib.error.generic_cross_origin_message');
        } else {
          content = app.translator.trans('talk.lib.error.generic_message');
        }
    }

    const isDebug: boolean = app.site.attribute('debug');

    error.alert = {
      type: 'error',
      content,
      controls: isDebug && [
        <Button className="Button Button--link" onclick={this.showDebug.bind(this, error, formattedErrors)}>
          {app.translator.trans('talk.lib.debug_button')}
        </Button>,
      ],
    };

    if (customErrorHandler) {
      customErrorHandler(error);
    } else {
      this.requestErrorDefaultHandler(error, isDebug, formattedErrors);
    }

    return Promise.reject(error);
  }

  /**
   * 用于修改页面上显示的错误消息，以帮助进行故障排除。
   * 虽然不确定，但失败的跨域请求可能表示缺少对 Talk 规范网址的重定向。
   * 由于 XHR 错误不会公开 CORS 信息，因此我们只能将请求的 URL 来源与页面来源进行比较。
   *
   * @param error
   * @protected
   */
  protected requestWasCrossOrigin(error: RequestError): boolean {
    return new URL(error.options.url, document.baseURI).origin !== window.location.origin;
  }

  protected requestErrorDefaultHandler(e: unknown, isDebug: boolean, formattedErrors: string[]): void {
    if (e instanceof RequestError) {
      if (isDebug && e.xhr) {
        const { method, url } = e.options;
        const { status = '' } = e.xhr;

        console.group(`${method} ${url} ${status}`);

        if (formattedErrors.length) {
          console.error(...formattedErrors);
        } else {
          console.error(e);
        }

        console.groupEnd();
      }

      if (e.alert) {
        this.requestErrorAlert = this.alerts.show(e.alert, e.alert.content);
      }
    } else {
      throw e;
    }
  }

  private showDebug(error: RequestError, formattedError: string[]) {
    if (this.requestErrorAlert !== null) this.alerts.dismiss(this.requestErrorAlert);

    this.modal.show(RequestErrorModal, { error, formattedError });
  }

  /**
   * 构造具有给定名称的路由的 URL。
   */
  route(name: string, params: Record<string, unknown> = {}): string {
    const route = this.routes[name];

    if (!route) throw new Error(`Route '${name}' does not exist`);

    const url = route.path.replace(/:([^\/]+)/g, (m, key) => `${extract(params, key)}`);

    // 删除参数中的虚假值，以避免出现类似 '/？sort&q' 的 url
    for (const key in params) {
      if (params.hasOwnProperty(key) && !params[key]) delete params[key];
    }

    const queryString = m.buildQueryString(params as any);
    const prefix = m.route.prefix === '' ? this.site.attribute('basePath') : '';

    return prefix + url + (queryString ? '?' + queryString : '');
  }
}
