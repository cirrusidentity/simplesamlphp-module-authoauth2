<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Microsoft as an AuthSource](#microsoft-as-an-authsource)
- [Usage](#usage)
  - [Recommended Config](#recommended-config)
  - [Gotchas](#gotchas)
- [Creating Microsoft Converged app](#creating-microsoft-converged-app)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Microsoft as an AuthSource

Microsoft has provided several login methods over the years, and the correct way to integrated depends on what sort
of backwards compatability you need, if you want global or pari-wise identifiers, and lasty if your client app will
authenticate with certificates or with username and password.

## Microsoft Attributes

* Microsoft returns both pair-wise ids (`sub`) and globaly unique ids (`oid`)
* No email for Azure accounts without O365 subscription

### ID Token

* Full name but not name components
* Email only for outlook and O365 users (not for Azure without O365 accounts)
* Global ID in `oid`
  * Live IDs are prefixed with 000
* `sub` is pair-wise id

### User Info

* Name components
* Email only for outlook and O365 users (not for Azure without O365 accounts)
* sub is pair-wise

### ME Endpoint

* Name components
* `id` is global (`oid` from `id token`)
  * For non-live, non-outlook this is the same as `oid` from `id token`
  * For outlook users this is `oid` without the leading `0`




# Usage

TODO: need to redo this section. Azure recommends apps use certificates (really to sign an authn jwt)
for authentication and the `OpenIDConnect` class doesn't support that directly (and even if it did,
how the jwt is populated is Azure specific)

## Pair-wise ids

Microsoft returns a pair-wise id (an id that is unique for your app) if you use OIDC. If a
pair-wise id is OK for your application then you can use `OpenIDConnect` to authenticate users
and use `oidc2name.php` to map OIDC claim names to LDAP friendly names.

## Global IDs

If you prefer a global identifier then you can use `OpenIDConnect` with `msglobal2name.php` to
map claims. This uses `oid`, the users global id, for `uid` instead for `sub`

## Global IDs with Live compatability

An outlook user's `oid` looks like `00000000-0000-0000-a76d-6a7a097c1e9d`, however if you
previously used MS Live SDK the user had an ID of `a76d6a7a097c1e9d`. To authenticate with
MS and convert Outlook users IDs to the old format do

TBD

## No longer recommended Config

This configuration was used when Microsoft was less consistent on which attributes were returned in
each from various endpoints for the different types of users. There now seems to be more consistency,
and this is no longer needed.

We ended up creating a sub class of the generic `authsource` called `MicrosoftHybridAuth`. This is because the OIDC `id_token`
and the response from the graph api contain different sets of attributes. For example for consumer users (e.g. hotmail or outlook.com)
the `id_token` will provide email but not first name and last name, while the graph api will provide first name and last name
but not email. The subclass uses the profile data from the graph api and the email and full name from the OIDC `id_token`



```php
   //authsources.php
   'microsoft' => [
       'authoauth2:MicrosoftHybridAuth',
       'clientId' => 'my-client',
       'clientSecret' => 'eyM-mysecret'
   ],
```

and if are using this with a SAML IdP then you can map the OIDC attributes to regular friendly names in your `authproc` section of `saml20-idp-hosted.php`.

```php
    // saml20-idp-hosted.php
$metadata['myEntityId'] = array(			
    'authproc' => array(
        // Convert oidc names to ldap friendly names
        90 => array('class' => 'core:AttributeMap',  'authoauth2:microsoft2name'),
    ),
   // other IdP config options
)
```
## Gotchas

* Azure AD only seems to return an email address if the user has an O365 subscription.
* The Graph OIDC user info endpoint only returns a targeted `sub` id. The `id_token` has
to be inspected to find the email address.

# Testing Permutations

<table>
<tr>
<td> User Type </td> <td> id_token </td> <td> User Info Response (OIDC) </td> <td> ME Response</td>
</tr>
<tr>
<td> Outlook </td>
<td>

```json
{
   "ver":"2.0",
   "iss":"https://login.microsoftonline.com/9188040d-6c67-4c5b-b112-36a304b66dad/v2.0",
   "sub":"AAAAAAAAAAAAAAAAAAAAABc7imd7VkiZjJG1vVb0kWk",
   "aud":"ed17d988-11b3-4df8-9faf-d5fce1c09f32",
   "exp":1664669176,
   "iat":1664582476,
   "nbf":1664582476,
   "name":"Steve Stratus",
   "preferred_username":"steve.stratus@outlook.com",
   "oid":"00000000-0000-0000-a76d-6a7a097c1e9d",
   "email":"steve.stratus@outlook.com",
   "tid":"9188040d-6c67-4c5b-b112-36a304b66dad",
   "aio":"DTViWkDgMEEk..snip..Z9utSO62M*1CJKu1e1nmSeiahDBU!6g$"
}
```

</td>

<td>

```json
{

   "@odata.context":"https://graph.microsoft.com/v1.0/$metadata#users/$entity",
   "displayName":"Steve Stratus",
   "surname":"Stratus",
   "givenName":"Steve",
   "id":"a76d6a7a097c1e9d",
   "userPrincipalName":"steve.stratus@outlook.com",
   "businessPhones":[

   ],
   "jobTitle":null,
   "mail":null,
   "mobilePhone":null,
   "officeLocation":null,
   "preferredLanguage":null
}
```

</td>

<td>

```json

{
   "sub":"AAAAAAAAAAAAAAAAAAAAANDpEqCGkyOcUQCMzG8tFaE",
   "name":"Steve Stratus",
   "given_name":"Steve",
   "family_name":"Stratus",
   "email":"steve.stratus@outlook.com",
  "picture":"https://graph.microsoft.com/v1.0/me/photo/$value"
}
```

</td>
</tr>

<tr>
<td> Azure no domain validation </td>
<td>

```json
{
   "aud":"9e7de22e-3a17-44fd-87c7-c6eceb1bee0e",
   "iss":"https://login.microsoftonline.com/61f237bd-a18f-4c2f-8746-f8fcabee8756/v2.0",
   "iat":1664820907,
   "nbf":1664820907,
   "exp":1664824807,
   "aio":"ATQAy/8TAAAAliQsr/6RmpguKgoWgvm2Jv81/4/Q16IaAwYEs8k1elC7CHUm/8ZYA0clgXSmPRZo",
   "name":"Homer Simpson",
   "oid":"cd914365-1c4a-48a3-a39a-bcb157bee237",
   "preferred_username":"hsimpson@tenantname.onmicrosoft.com",
   "rh":"0.ARsAvTfyYY-hL0yHRvj8q-6HVi7ifZ4XOv1Eh8fG7Osb7g4bAE4.",
   "sub":"RVRqiP7s_gsHFK2uAjREcnifgsQdiSa96xAVhKDSkoo",
   "tid":"61f237bd-a18f-4c2f-8746-f8fcabee8756",
   "uti":"JnuQl8WMNkKcnT_GKmFHAA",
   "ver":"2.0"
}
```

</td>

<td>

```json
{
   "@odata.context":"https://graph.microsoft.com/v1.0/$metadata#users/$entity",
   "businessPhones":[

   ],
   "displayName":"Homer Simpson",
   "givenName":"Org",
   "jobTitle":null,
   "mail":null,
   "mobilePhone":null,
   "officeLocation":null,
   "preferredLanguage":"en-US",
   "surname":"Simpson",
   "userPrincipalName":"hsimpson@tenantname.onmicrosoft.com",
   "id":"cd914365-1c4a-48a3-a39a-bcb157bee237"
}
```

</td>

<td>

```json
{
   "sub":"RVRqiP7s_gsHFK2uAjREcnifgsQdiSa96xAVhKDSkoo",
   "name":"Homer Simpson",
   "family_name":"Simpson",
   "given_name":"Homer",
   "picture":"https://graph.microsoft.com/v1.0/me/photo/$value"
}```

</td>
</tr>

<tr>
<td> Azure, no O365, domain validation </td>
<td>

```json
{
   "aud":"9e7de22e-3a17-44fd-87c7-c6eceb1bee0e",
   "iss":"https://login.microsoftonline.com/340b0ada-eabc-4321-bbcb-caef8516f00a/v2.0",
   "iat":1664582014,
   "nbf":1664582014,
   "exp":1664585914,
   "aio":"ATQAy/8TAAAAx5QAoQSJlg8szmBOtmpShMPSBUFHaUL7OUJM2I3CIs06oHlBlPs+lXuIPnrv0DI2",
   "name":"Monitor User",
   "oid":"080bb855-31d1-4277-bff3-d56484654195",
   "preferred_username":"monitor@athena-institute.net",
   "rh":"0.AS4A2goLNLzqIUO7y8rvhRbwCi7ifZ4XOv1Eh8fG7Osb7g4uAM0.",
   "sub":"JuaSrk3WCx-3sWzgBlcCzsjx2ZIPzGZLT_gekSo4D6Q",
   "tid":"340b0ada-eabc-4321-bbcb-caef8516f00a",
   "uti":"vX2q_NAR50S2nhB99XXMAA",
   "ver":"2.0"
}
```

</td>

<td>

```json
{
   "@odata.context":"https://graph.microsoft.com/v1.0/$metadata#users/$entity",
   "businessPhones":[

   ],
   "displayName":"Monitor User",
   "givenName":"Monitor",
   "jobTitle":null,
   "mail":null,
   "mobilePhone":null,
   "officeLocation":null,
   "preferredLanguage":null,
   "surname":"User",
   "userPrincipalName":"monitor@athena-institute.net",
   "id":"080bb855-31d1-4277-bff3-d56484654195"
}
```

</td>

<td>

```json
{
   "sub":"JuaSrk3WCx-3sWzgBlcCzsjx2ZIPzGZLT_gekSo4D6Q",
   "name":"Monitor User",
   "family_name":"User",
   "given_name":"Monitor",
   "picture":"https://graph.microsoft.com/v1.0/me/photo/$value"
}
```

</td>
</tr>

<tr>
<td> Azure, w O365 and domain validation </td>
<td>

```json
{
   "aud":"ed17d988-11b3-4df8-9faf-d5fce1c09f32",
   "iss":"https://login.microsoftonline.com/817f5904-3904-4ee8-b3a5-a65d4746ff70/v2.0",
   "iat":1664581345,
   "nbf":1664581345,
   "exp":1664585245,
   "aio":"ATQAy/8TAAAA3wHKuSV8tmrQ3i1CDac6OSw0qxdN91pmR7v/Vau1y5erMiszRYook5owieV6g1IV",
   "email":"Test.Sso@example.edu",
   "name":"Test Sso",
   "oid":"2e02611e-a8b9-4612-a554-026154495a1b",
   "preferred_username":"tisa2020@example.edu",
   "rh":"0.AVcABFl_gQQ56E6zpaZdR0b_cIjZF-2zEfhNn6_V_OHAnzJXAFI.",
   "sub":"aJPoIKyby53TrHjaz-1BC2QFKaFWdYPdAe1oi9XMi10",
   "tid":"817f5904-3904-4ee8-b3a5-a65d4746ff70",
   "uti":"ISFp3OJs7UiAa5Rg2r-3AA",
   "ver":"2.0"
}
```

</td>

<td>

```json
{
   "@odata.context":"https://graph.microsoft.com/v1.0/$metadata#users/$entity",
   "businessPhones":[

   ],
   "displayName":"Test Sso",
   "givenName":"Test",
   "jobTitle":null,
   "mail":"Test.Sso@example.edu",
   "mobilePhone":null,
   "officeLocation":null,
   "preferredLanguage":null,
   "surname":"Sso",
   "userPrincipalName":"tisa2020@example.edu",
   "id":"2e02611e-a8b9-4612-a554-026154495a1b"
}
```

</td>

<td>

```json
{
   "sub":"55ssCLhlLRo8J2RLfn_9bzLL-6-hlO-vgny5liQWDhU",
   "name":"Test Sso",
   "family_name":"Sso",
   "given_name":"Test",
   "picture":"https://graph.microsoft.com/v1.0/me/photo/$value",
   "email":"Test.Sso@example.edu"
}
```

</td>
</tr>


</table>


# Managing Microsoft Live Apps (old)

You can no longer create Live SDK apps but you can view your current ones by visiting https://apps.dev.microsoft.com



