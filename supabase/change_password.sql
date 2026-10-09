-- Lets the Android app's Settings page change a user's password.
-- Only changes it when the current password matches; returns false otherwise.
-- Run in Supabase: SQL Editor -> New query -> paste -> Run

create or replace function public.change_password(p_user_code text, p_current text, p_new text)
returns boolean
language plpgsql
security definer
set search_path = public
as $$
begin
  if length(p_new) < 6 then
    raise exception 'New password must be at least 6 characters';
  end if;

  update users
     set password = p_new
   where user_code = p_user_code
     and password = p_current;

  return found;
end;
$$;

revoke execute on function public.change_password(text, text, text) from public;
grant execute on function public.change_password(text, text, text) to anon;
