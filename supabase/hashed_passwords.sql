-- Passwords are now bcrypt hashes ($2a$...). This updates the Android app's database
-- functions to match, the same way app_login() already checks them:
--   change_password  checks the current password against the hash, saves the new one hashed
--   change_username  checks the password against the hash
-- Accounts still holding a plain-text password keep working (same as the website).
-- It also stops the app's public key from reading password hashes out of the users table.
--
-- Run in Supabase: SQL Editor -> New query -> paste the WHOLE file -> Run

create or replace function public.change_password(p_user_code text, p_current text, p_new text)
returns boolean
language plpgsql
security definer
set search_path = public, extensions
as $fn$
begin
  if length(trim(p_new)) < 6 then
    raise exception 'New password must be at least 6 characters';
  end if;

  update users
     set password = extensions.crypt(trim(p_new), extensions.gen_salt('bf', 10))
   where user_code = p_user_code
     and case
           when password like '$2%' then password = extensions.crypt(trim(p_current), password)
           else password = trim(p_current)
         end;

  return found;
end;
$fn$;

revoke execute on function public.change_password(text, text, text) from public;
grant execute on function public.change_password(text, text, text) to anon;


create or replace function public.change_username(p_user_code text, p_password text, p_username text)
returns text
language plpgsql
security definer
set search_path = public, extensions
as $fn$
declare
  v_name text := lower(trim(p_username));
begin
  if v_name !~ '^[a-z0-9._-]{3,30}$' or v_name like 'svob-%' then
    return 'invalid';
  end if;

  if not exists (
    select 1 from users
     where user_code = p_user_code
       and case
             when password like '$2%' then password = extensions.crypt(trim(p_password), password)
             else password = trim(p_password)
           end
  ) then
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
$fn$;

revoke execute on function public.change_username(text, text, text) from public;
grant execute on function public.change_username(text, text, text) to anon;


-- The app now logs in through app_login(), so it only needs to read user codes and
-- usernames (Settings shows the username). Password hashes stay hidden.
revoke select on public.users from anon;
grant select (user_code, username, role) on public.users to anon;
