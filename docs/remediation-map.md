# Remediation Map (Production Canon)


## P0 — compile/runtime blockers




## P1 — canon architecture

- [ ] Remove static service/repository calls (scan_violations: 60)
- [ ] Normalize non-final classes where inheritance is not required
- [ ] Remove facade/helper direct usage in app/** where service injection is required


## P1 — frontend quality (.vue)

- [ ] Reduce inline `style=` usage in shared/layout components first


## Protocol

- After each fix, remove the corresponding completed line from this file.
