import Link from 'next/link';
import type { ComponentProps, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'ghost' | 'ghostInverse';

const base =
  'inline-flex items-center justify-center gap-2 rounded-md px-6 py-3 text-sm font-semibold transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60 disabled:active:scale-100';

const variants: Record<Variant, string> = {
  primary:
    'bg-emerald-600 text-white hover:bg-emerald-500 disabled:hover:bg-emerald-600',
  secondary:
    'bg-white text-zinc-900 hover:bg-zinc-100 disabled:hover:bg-white',
  ghost:
    'border border-zinc-300 text-zinc-900 hover:bg-zinc-100 disabled:hover:bg-transparent',
  ghostInverse:
    'border border-white/30 text-white hover:bg-white/10 disabled:hover:bg-transparent',
};

type CommonProps = {
  variant?: Variant;
  className?: string;
  children: ReactNode;
};

type ButtonAsButton = CommonProps &
  Omit<ComponentProps<'button'>, 'className' | 'children'> & {
    href?: undefined;
  };

type ButtonAsLink = CommonProps & {
  href: string;
};

export type ButtonProps = ButtonAsButton | ButtonAsLink;

export function Button(props: ButtonProps) {
  const { variant = 'primary', className = '', children } = props;
  const cls = `${base} ${variants[variant]} ${className}`.trim();

  if ('href' in props && props.href) {
    return (
      <Link href={props.href} className={cls}>
        {children}
      </Link>
    );
  }

  const { href: _href, variant: _v, className: _c, children: _ch, ...rest } =
    props as ButtonAsButton & { href?: undefined };
  void _href;
  void _v;
  void _c;
  void _ch;

  return (
    <button {...rest} className={cls}>
      {children}
    </button>
  );
}
