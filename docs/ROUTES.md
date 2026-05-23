# Routes & URL Mapping

## .htaccess Routing

The application uses Apache `.htaccess` for URL routing. Clean URLs map to PHP files.

## Mapping Table

| URL | Maps To | Purpose |
|-----|---------|---------|
| `/EditDailySheet` | EditDailySheet.php | Edit timesheet for a specific date |
| `/ViewAs` | ViewAs.php | View homepage as another role (128+ only) |
| `/home` | home.php | Main dashboard |
| `/EditMyDetails` | members-new.php | Edit own details (member) |
| `/AllMembers` | members-list-v2b.php | Member list (modern) |
| `/Member` | members.php | Add/edit member (legacy) |
| `/MemberNew` | members-new.php | Add/edit member (modern) |
| `/UsersList` | users-list-v2b.php | User list (modern) |
| `/UsersListOld` | users-list.php | User list (legacy) |
| `/Users` | users-new.php | Add/edit user (modern) |
| `/UsersOld` | users.php | Add/edit user (legacy) |
| `/AllAircraft` | aircraft-list.php | Aircraft list |
| `/Aircraft` | aircraft.php | Add/edit aircraft |
| `/AircraftTypes` | aircrafttype-list.php | Aircraft types |
| `/AircraftType` | aircrafttype.php | Add/edit type |
| `/DailySheet` | dailysheet.php | Flight entry |
| `/AllFlights` | flights-list.php | Flight list |
| `/AllFlightsMobile` | AllFlightsReportMobile.php | All flights (mobile-friendly cards) |
| `/AllFlightsReportNew` | AllFlightsReportNew.php | All flights (DataTables desktop) |
| `/FlightTypes` | flighttypes-list.php | Flight types |
| `/FlightType` | flighttypes.php | Add/edit type |
| `/LaunchTypes` | launchtypes-list.php | Launch types |
| `/LaunchType` | launchtypes.php | Add/edit type |
| `/BillingOptions` | billingoptions-list.php | Billing options |
| `/BillingOption` | billingoptions.php | Add/edit option |
| `/TowCharges` | towcharges-list.php | Tow pricing |
| `/TowCharge` | towcharges.php | Add/edit tow charge |
| `/OtherCharges` | charges-list.php | Other fees |
| `/OtherCharge` | charges.php | Add/edit charge |
| `/DutyTypes` | dutytypes-list.php | Duty types |
| `/DutyType` | dutytypes.php | Add/edit duty type |
| `/Rosters` | duty-list.php | Roster list |
| `/Roster` | duty.php | Add/edit duty |
| `/IncentiveSchemes` | incentive_schemes-list.php | Pricing schemes |
| `/IncentiveScheme` | incentive_schemes.php | Add/edit scheme |
| `/SubsToSchemes` | scheme_subs-list.php | Subscriptions |
| `/SubsToScheme` | scheme_subs.php | Add/edit subscription |
| `/Roles` | roles-list.php | Role list |
| `/Role` | roles.php | Add/edit role |
| `/AssignRoles` | role_member-list.php | Role assignments |
| `/AssignRole` | role_member.php | Assign role |
| `/Groups` | groups-list.php | Group list |
| `/Group` | groups.php | Add/edit group |
| `/MessagingPage` | MessagingPage.php | Broadcast message |
| `/MyFlights` | MyFlights.php | Member's flights |
| `/FlyingNow` | FlyingNow.php | Current flights status |
| `/PasswordChange` | PasswordChange.php | Change password |
| `/Organisations` | organisations-list.php | Org list (super admin) |
| `/Organisation` | organisations.php | Add/edit org |
| `/Audits` | audit-list.php | Audit log |
| `/Spots` | spots-list.php | Tracking devices |
| `/Spot` | spots.php | Add/edit spot |
| `/membership_class` | membership_class-list.php | Member classes |
| `/membership_status` | membership_status-list.php | Member statuses |

## Special Routes

| URL | Maps To | Purpose |
|-----|---------|---------|
| `/app/*` | /lrv/public/app/* | Laravel assets |
| `/app/css/*` | /lrv/public/css/* | Laravel CSS |
| `/api/v1/json/*` | apiglidjsonv1.php | Internal JSON API |
| `/api/v2/*` | /lrv/public/api/v2/* | Laravel API routes |
| `/api/members` | api/members.php | DataTables member list |
| `/api/member-form` | api/member-form.php | Member form API (GET: classes/statuses/roles, POST: save) |
| `/api/users` | api/users.php | DataTables user list |
| `/api/user-form` | api/user-form.php | User form API (GET: orgs/members, POST: save) |
| `/GlideAccounts.csv` | Treasurer2.php | Treasurer CSV export |
| `/Engineering.csv` | Engineer2.php | Engineer CSV export |
| `/OlcFile.igc` | igcgenerate.php | IGC file download |
| `/wgc-new` | MasterDisplayNew.php?org=1 | Real-time map (new Leaflet version) |
| `/wgc` | MasterDisplay.php?org=1 | Wellington club map |
| `/ssb` | MasterDisplay.php?org=2 | SSB club map |
| `/cgc` | MasterDisplay.php?org=3 | CGC club map |
| `/agc` | MasterDisplay.php?org=4 | AGC club map |
| `/SentMessages` | texts-list-v2b.php | Sent messages table view |
| `/MessagesTree` | messages-tree.php | Sent messages treeview |
| `/MessagesTreeOld` | messages-tree.php | (alias) |
| `/oauth-login` | oauth-login.php | Initiate OAuth sign-in with Google/Facebook |
| `/oauth-callback` | oauth-callback.php | OAuth provider callback (token exchange, session create) |
| `/oauth-link` | oauth-link.php | Link social account to existing user |
| `/oauth-link-action` | oauth-link-action.php | Process account linking form submission |

## Security Routes

These redirect to login:
- Any page without proper session

## Legacy/Deprecated Routes

Some routes exist but point to dead features:
- Some role_* routes
- Some group_* routes
- Backup routes in various places

## How Routing Works

1. Apache reads `.htaccess`
2. RewriteRule patterns match URL
3. Internal redirect to target PHP file
4. Query parameters preserved (e.g., `?id=123&org=1`)

Example:
```
/Member?id=45 → members.php?id=45
/AllAircraft?col=2 → aircraft-list.php?col=2
```

## Adding New Routes

To add a clean URL:
1. Add to `.htaccess`:
   ```
   RewriteRule ^new-route-name$ target-file.php [L]
   ```
2. Create target PHP file
3. Update documentation