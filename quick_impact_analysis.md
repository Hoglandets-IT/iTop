An attempt to accelerate the process of impact analysis by relying on recursive queries

# TODO to prove the concept
- [x] Integrate the recursive query into the process of building the impact graph
- [x] Improve to cope with backtracking on redundancy nodes
- [ ] Optimize context management
  - [x] Reduce the number of queries
  - [ ] See how to cache the queries (IN clause prevents caching)
- [ ] Restore redundancy computation

# TODO to finalize the feature
- [ ] Deshardcode the query against the source nodes
- [ ] Deshardcode the query against the datamodel
    - [ ] Get the algorithm from make_quick_impact_query.php
    - [ ] Find a clean way to hack queries
    - [ ] Eliminate the limitation on unions
    - [ ] Cache (parts of) the query
- [ ] Review the KPIs
- [ ] Decide if the legacy algorithm should be kept (opt-in/out)
- [ ] Do not load persistent objects in the impact graph as they are not needed (tooltip loaded by ajax calls)

# Metrics

To to build the impact graph with 520 CIs

| Operation           | Legacy | Optimized | Fix overhead in optimized version | Time per CI in optimized version |
|---------------------|--------|-----------|-----------------------------------|------------------|
| Build logical graph | 2.5 s  | 0.4 s     | 0 s                               | 0,7 ms          |
| Integrate context   | 2 s    | 0.8 s     | 0,78 s                            | 0,1 ms          |
| Graphviz            | 0.4 s  | 0.4 s     | 0,4 s                             | 0 ms         |
| Total               | 4.9 s  | 1.6 s     | 1.18 s                            | 0,8 ms          |

Comments:
 - Graphviz execution time is the time for Windows to spawn the process, so it is not really relevant
 - In the optimized version, building the logical graph still require to instantiate the objects which is done with one query per class, but it does not seem to be a must
 - For small graphs, the overhead on context queries can be questioned