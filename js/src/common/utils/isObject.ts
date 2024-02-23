/**
 * Returns if the passed value is an object.
 *
 * In this context, "object" refers to **any non-primitive value**, including
 * arrays, function, maps, dates, and more.
 *
 * @example
 * isObject({}); // true
 * @example
 * isObject([]); // true
 * @example
 * isObject(function () {}); // true
 * @example
 * isObject(Object(1)); // true
 * @example
 * isObject(null); // false
 * @example
 * isObject(1); // false
 * @example
 * isObject("hello world"); // false
 *
 */
export default function isObject(obj: unknown): obj is object {
  const type = typeof obj;
  return type === 'function' || (type === 'object' && !!obj);
}
