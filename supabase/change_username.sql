-- Lets the Android app's Settings page set or change a user's username (used to log in).
-- Needs the user's password. Returns:
--   'ok'             saved
--   'invalid'        not 3-30 of a-z 0-9 . _ - (or starts with svob-, like an account number)
--   'wrong_password' password doesn't match
--   'taken'          someone else already has it
-- Usernames are saved in lowercase.
-- Run in Supabase: SQL Editor -> New query -> paste the WHOLE file -> Run

create or replace function public.change_username(p_user_code text, p_password text, p_username text)
returns text
language plpgsql
security definer
set search_path = public
as $$
declare
  v_name text := lower(trim(p_username));
begin
  if v_name !~ '^[a-z0-9._-]{3,30}$' or v_name like 'svob-%' then
    return 'invalid';
  end if;

  if not exists (select 1 from users where user_code = p_user_code and password = p_password) then
    return 'wrong_password';
  end if;

  if exists (select 1 from users where lower(username) = v_name and user_code <> p_user_code) then
    return 'taken';
  end if;

  update users set username = v_name where user_code = p_user_code;
  return 'ok';

exception when unique_violation then
  return 'taken';
end;
$$;

revoke execute on function public.change_username(text, text, text) from public;
grant execute on function public.change_username(text, text, text) to anon;
