// my makefile: g++ -g -o main main.cpp -lgmpxx -lgmp

#include <iostream>
#include <string>
#include <sstream>
#include <vector>
#include <set>
#include <cmath>
#include <gmpxx.h>

using namespace::std;

string trim(const string &s) {
    size_t start = s.find_first_not_of(" \n\r\t");

    return s.substr(start, s.find_last_not_of(" \n\r\t") - start + 1);
}

vector<string> read_ordered_set()
{
    string s;
    getline (cin, s);

    set<string> sset;
    stringstream ss(s);
    string item;
    while (getline(ss, item, ' ')) {
        sset.insert(trim(item));
    }

    vector<string> v;
    v.assign(sset.begin(), sset.end());

    sort(v.begin(), v.end());

    return v;
}

void display(int cardinality, vector<string> &roster) {
    cout << "cardinality: " << cardinality << endl;
    cout << "roster: ";
    if (cardinality == 0) {
        cout << "empty" << endl;
    }
    else {
        cout << "{ ";
        for (int i = 0; i < roster.size() - 1; i++) {
            cout << roster[i] << ", ";
        }
        cout << roster[roster.size()-1] << " }" << endl;
        roster.clear();
    }
}

int intersection(vector<string>& a, vector<string>& b, vector<string>& roster) {
    roster.clear();

    vector<string>::iterator ia = a.begin();
    vector<string>::iterator ib = b.begin();

    while (ia != a.end() && ib != b.end()) {
        if (*ia == *ib) {
            roster.push_back(*ia);
            ++ia, ++ib;
        } else if (*ia < *ib) {
            ++ia;
        } else {
            ++ib;
        }
    }

    return roster.size();
}

int uni (vector<string> a, vector<string> b, vector<string> &roster) {
    int c = 0;
    for (int i = 0; i < a.size(); i++) {
        roster.push_back(a[i]);
        c++;
    }
    bool check = false;
    for (int i = 0; i < b.size(); i++) {
        for (int j = 0; j < a.size(); j++) {
            if (b[i] == roster[j]) {
                check = true;
            }
        }
        if (!check) {
            roster.push_back(b[i]);
            c++;
        }
        else {
            check = false;
        }
    }
    return c;
}

int rcomplement(vector<string> a, vector<string> b, vector<string> &roster) {
    int c = 0;
    bool check = false;
    for (int i = 0; i < a.size(); i++) {
        for (int j = 0; j < b.size(); j++) {
            if(a[i] == b[j]) {
                check = true;
            }
        }
        if (!check) {
            roster.push_back(a[i]);
            c++;
        }
        else {
            check = false;
        }
    }
    return c;
}

int cproduct(vector<string> a, vector<string> b, vector<string> &roster) {
    int c = 0;
    string str;
    char delimiter = ',';
    for (int i = 0; i < a.size(); i++) {
        for (int j = 0; j < b.size(); j++) {
            str = a[i] + delimiter + b[j];
            c++;
            roster.push_back(str);
        }
    }
    return c;
}

mpz_class pcproduct(vector<string> a, vector<string> b) {
    return pow(2, a.size() * b.size());
}

int main() {
    vector<string> roster;
    int cardinality = 0;
  
    cout << "Please, enter a set A" << endl;
    vector<string> A = read_ordered_set();

    cout << "Please, enter a set B" << endl;
    vector<string> B = read_ordered_set();

    cout << endl << "Set A: ";
    for(int i = 0; i < A.size(); i++) {
        cout << A[i] << " ";
    }
    cout << endl << "Set B: ";
    for(int i = 0; i < B.size(); i++) {
        cout << B[i] << " ";
    }
    cout << endl;
    cardinality = intersection(A, B, roster);
    cout << "1. The cardinality and roster of the intersection of A and B:" << endl;
    display(cardinality, roster);

    cardinality = uni(A, B, roster);
    cout << "2.  The cardinality and roster of the union of A and B: " << endl;
    display(cardinality, roster);

    cardinality = rcomplement(A, B, roster);
    cout << "3.  The cardinality and roster of the relative complement of A and B (i.e. A - B): " << endl;
    display(cardinality, roster);

    cardinality = rcomplement(B, A, roster);
    cout << "4.  The cardinality and roster of the relative complement of B and A (i.e. B - A): " << endl;
    display(cardinality, roster);
    
    cardinality = cproduct(A, B, roster);
    cout << "5.  The cardinality and roster of the cross product of A and B: " << endl;
    display(cardinality, roster);

    cout << "6.  The cardinality of the power set of the cross product of A and B: " << endl;
    cout << "cardinality: " << pcproduct(A, B) << endl;

    return 0;
}
